<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\Farm;
use App\Models\FarmAuditLog;
use App\Models\FarmUser;
use App\Models\User;
use App\Services\AiUsageService;
use App\Services\CurrentFarm;
use App\Services\FarmAuditService;
use App\Services\FarmStaffService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Ferma egasi xodimlarni boshqaradi (huquq: staff.manage). Xodim uchun alohida ferma yaratilmaydi.
 */
class StaffController extends Controller
{
    // Egasi faqat shu rollarni bera oladi
    public const ASSIGNABLE_ROLES = [FarmUser::ROLE_MANAGER, FarmUser::ROLE_WORKER, FarmUser::ROLE_VIEWER];

    // Vaqtinchalik parol sessiyaga faqat shifrlangan holda, bir martalik flash bo'lib yoziladi
    private const CREDENTIALS_FLASH = 'staff_credentials';

    public function __construct(private FarmAuditService $audit)
    {
    }

    public function index(AiUsageService $usage)
    {
        return response()
            ->view('staff.index', $this->pageData($this->currentFarm(), $usage) + [
                'credentials' => $this->pullCredentials(),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, FarmStaffService $staff)
    {
        $farm = $this->currentFarm();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $email = Str::lower(trim($validated['email']));
        $password = null;
        $member = null;

        $error = DB::transaction(function () use ($farm, $validated, $email, $staff, &$password, &$member) {
            // Bir vaqtda ikki so'rov limitdan oshirib yubormasligi uchun ferma qatori qulflanadi
            $farm = Farm::whereKey($farm->id)->lockForUpdate()->first();

            if (!$farm->hasStaffCapacity()) {
                return $this->limitMessage($farm);
            }

            $member = User::where('email', $email)->first();

            if ($member && $farm->memberships()->where('user_id', $member->id)->exists()) {
                return __('Bu foydalanuvchi allaqachon fermangiz a\'zosi.');
            }

            if (!$member) {
                $password = $staff->generatePassword();
                $member = User::create([
                    'name' => $validated['name'],
                    'email' => $email,
                    'password' => $password,
                ]);
                $member->forceFill(['current_farm_id' => $farm->id])->save();
            }

            $farm->members()->attach($member->id, [
                'role' => $validated['role'],
                'status' => FarmUser::STATUS_ACTIVE,
                'invited_by' => auth()->id(),
                'must_change_password' => $password !== null,
            ]);

            return null;
        });

        if ($error !== null) {
            return back()->withInput()->withErrors(['staff' => $error]);
        }

        $this->audit->log($farm, FarmAuditLog::ACTION_STAFF_ADDED, 'staff', $member->id, [], [
            'ism' => $member->name,
            'email' => $member->email,
            'rol' => $validated['role'],
            'yangi_akkaunt' => $password !== null,
        ]);

        $message = $password !== null
            ? __(':name xodim sifatida qo\'shildi. Vaqtinchalik parolni hozir nusxalab oling — u boshqa ko\'rsatilmaydi.', ['name' => $member->name])
            : __(':name fermaga a\'zo qilindi. U o\'z paroli bilan kiradi.', ['name' => $member->name]);

        if ($password !== null) {
            $this->flashCredentials($member, $password);
        }

        return redirect()->route('staff.index')->with('success', $message);
    }

    /**
     * Parolni hali almashtirmagan xodimga yangi vaqtinchalik parol. Faqat shu fermada yaratilgan akkauntga ruxsat.
     */
    public function resetPassword(FarmUser $member, FarmStaffService $staff)
    {
        $farm = $this->currentFarm();

        if ((int) $member->farm_id !== (int) $farm->id) {
            abort(404);
        }

        abort_unless($member->canResetTemporaryPassword($farm), 403);

        $user = $member->user;
        $password = $staff->generatePassword();
        $user->forceFill(['password' => $password])->save();

        // Parolning o'zi jurnalga yozilmaydi
        $this->audit->log($farm, FarmAuditLog::ACTION_STAFF_PASSWORD_RESET, 'staff', $user->id, [], [
            'ism' => $user->name,
            'email' => $user->email,
        ]);

        $this->flashCredentials($user, $password);

        return redirect()->route('staff.index');
    }

    public function updateRole(Request $request, FarmUser $member)
    {
        $farm = $this->currentFarm();
        $this->ensureManageable($farm, $member);

        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $oldRole = $member->role;

        if ($oldRole === $validated['role']) {
            return redirect()->route('staff.index');
        }

        $member->role = $validated['role'];
        $member->save();

        $this->audit->log($farm, FarmAuditLog::ACTION_STAFF_ROLE_CHANGED, 'staff', $member->user_id,
            ['rol' => $oldRole],
            ['rol' => $member->role, 'ism' => $member->user?->name]
        );

        return redirect()->route('staff.index')->with('success', __(':name roli o\'zgartirildi.', ['name' => $member->user?->name]));
    }

    public function updateStatus(FarmUser $member, SubscriptionService $subscriptions)
    {
        $farm = $this->currentFarm();
        $this->ensureManageable($farm, $member);

        $activate = $member->status !== FarmUser::STATUS_ACTIVE;

        // Faqat o'qish rejimida to'xtatish mumkin (xavfsizlik), qayta faollashtirish — yo'q
        if ($activate && $subscriptions->isReadOnly($farm)) {
            return redirect()->route('staff.index')->withErrors(['staff' => __("To'lov qilinmagani sababli faoliyat cheklangan: xodimni qayta faollashtirish to'lov qilinganidan keyin mumkin.")]);
        }

        $error = DB::transaction(function () use ($farm, $member, $activate) {
            $farm = Farm::whereKey($farm->id)->lockForUpdate()->first();

            // Faollashtirishda ham limit tekshiriladi (to'xtatilgan a'zo hozir hisobga kirmaydi)
            if ($activate && !$farm->hasStaffCapacity()) {
                return $this->limitMessage($farm);
            }

            $member->status = $activate ? FarmUser::STATUS_ACTIVE : FarmUser::STATUS_SUSPENDED;
            $member->save();

            return null;
        });

        if ($error !== null) {
            return redirect()->route('staff.index')->withErrors(['staff' => $error]);
        }

        $this->audit->log($farm, FarmAuditLog::ACTION_STAFF_STATUS_CHANGED, 'staff', $member->user_id,
            ['holat' => $activate ? FarmUser::STATUS_SUSPENDED : FarmUser::STATUS_ACTIVE],
            ['holat' => $member->status, 'ism' => $member->user?->name]
        );

        return redirect()->route('staff.index')->with('success', $activate
            ? __(':name faollashtirildi.', ['name' => $member->user?->name])
            : __(':name to\'xtatildi. U bu fermaga kira olmaydi.', ['name' => $member->user?->name]));
    }

    /**
     * Fermadan chiqarish: faqat a'zolik o'chadi, akkaunt va u qo'shgan ma'lumotlar qoladi.
     */
    public function destroy(FarmUser $member, CurrentFarm $currentFarm)
    {
        $farm = $this->currentFarm();
        $this->ensureManageable($farm, $member);

        $user = $member->user;

        DB::transaction(function () use ($member, $user, $farm) {
            $member->delete();

            if ($user && (int) $user->current_farm_id === (int) $farm->id) {
                $user->forceFill(['current_farm_id' => null])->save();
            }
        });

        if ($user) {
            $currentFarm->forget($user);
        }

        $this->audit->log($farm, FarmAuditLog::ACTION_STAFF_REMOVED, 'staff', $member->user_id, [
            'ism' => $user?->name,
            'email' => $user?->email,
            'rol' => $member->role,
        ]);

        return redirect()->route('staff.index')->with('success', __(':name fermadan chiqarildi.', ['name' => $user?->name]));
    }

    /**
     * Boshqa fermaning a'zoligi — 404. Egasining a'zoligi va o'zini o'zgartirib bo'lmaydi.
     */
    private function ensureManageable(Farm $farm, FarmUser $member): void
    {
        if ((int) $member->farm_id !== (int) $farm->id) {
            abort(404);
        }

        if ($member->role === FarmUser::ROLE_OWNER || (int) $member->user_id === (int) auth()->id()) {
            abort(redirect()->route('staff.index')->withErrors(['staff' => __('Ferma egasining a\'zoligini o\'zgartirib bo\'lmaydi.')]));
        }
    }

    private function flashCredentials(User $user, string $password): void
    {
        session()->flash(self::CREDENTIALS_FLASH, Crypt::encryptString(json_encode([
            'name' => $user->name,
            'email' => $user->email,
            'password' => $password,
        ])));
    }

    private function pullCredentials(): ?array
    {
        $encrypted = session(self::CREDENTIALS_FLASH);

        if (!is_string($encrypted)) {
            return null;
        }

        try {
            $credentials = json_decode(Crypt::decryptString($encrypted), true);
        } catch (DecryptException) {
            return null;
        }

        return is_array($credentials) ? $credentials : null;
    }

    private function limitMessage(Farm $farm): string
    {
        return __('Tarifingiz bo\'yicha ko\'pi bilan :limit ta xodim qo\'shish mumkin. Ko\'proq xodim kerak bo\'lsa, yuqoriroq tarifga o\'ting.', [
            'limit' => (int) $farm->staffLimit(),
        ]);
    }

    private function pageData(Farm $farm, AiUsageService $usage): array
    {
        $monthStart = $usage->monthStart();

        $members = $farm->memberships()
            ->with('user')
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [FarmUser::ROLE_OWNER])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Shu oydagi AI ishlatish: shu fermada, har bir a'zo bo'yicha
        $usageRows = AiUsageLog::where('farm_id', $farm->id)
            ->where('created_at', '>=', $monthStart)
            ->whereIn('user_id', $members->pluck('user_id'))
            ->selectRaw('user_id, turi, COUNT(*) AS soni')
            ->groupBy('user_id', 'turi')
            ->get();

        $aiUsage = [];

        foreach ($usageRows as $row) {
            $aiUsage[$row->user_id][$row->turi] = (int) $row->soni;
        }

        $limit = $farm->staffLimit();
        $activeStaff = $members->where('role', '!=', FarmUser::ROLE_OWNER)->where('status', FarmUser::STATUS_ACTIVE)->count();
        $suspendedStaff = $members->where('role', '!=', FarmUser::ROLE_OWNER)->where('status', FarmUser::STATUS_SUSPENDED)->count();

        return [
            'farm' => $farm,
            'members' => $members,
            'aiUsage' => $aiUsage,
            'staffLimit' => $limit,
            'activeStaff' => $activeStaff,
            'suspendedStaff' => $suspendedStaff,
            'hasCapacity' => $limit === null || $activeStaff < $limit,
            'roles' => self::ASSIGNABLE_ROLES,
        ];
    }
}
