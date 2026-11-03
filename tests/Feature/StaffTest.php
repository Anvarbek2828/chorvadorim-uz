<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\FarmAuditLog;
use App\Models\FarmUser;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Xodimlar B: xodimlar sahifasi, rollar, limit, majburiy parol, ferma jurnali, xavfsiz o'chirish, bot.
 */
class StaffTest extends TestCase
{
    use RefreshDatabase;

    private function farmOf(User $user): Farm
    {
        return $user->ownedFarms()->firstOrFail();
    }

    private function setPlan(Farm $farm, string $kod): void
    {
        $farm->plan_id = Plan::where('kod', $kod)->value('id');
        $farm->save();
    }

    private function animalIn(Farm $farm, ?User $creator = null, array $attributes = []): Animal
    {
        return Animal::create($attributes + [
            'farm_id' => $farm->id,
            'user_id' => ($creator ?? $farm->owner)->id,
            'name' => 'Sigir ' . $farm->id,
            'direction' => 'sut',
        ]);
    }

    private function addMember(Farm $farm, string $role, string $status = FarmUser::STATUS_ACTIVE): User
    {
        $member = User::factory()->create();
        // Factory har bir foydalanuvchiga o'z fermasini yaratadi; xodim faqat shu fermaga a'zo bo'lishi uchun olib tashlanadi
        $member->ownedFarms()->delete();
        $farm->members()->attach($member->id, ['role' => $role, 'status' => $status]);
        $member->forceFill(['current_farm_id' => $farm->id])->save();

        return $member;
    }

    private function passwordFrom(string $html): string
    {
        preg_match('/id="stf-password"[^>]*>([^<]+)</', $html, $matches);

        return trim($matches[1] ?? '');
    }

    public function test_worker_cannot_delete_animal(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $animal = $this->animalIn($farm);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);

        $this->actingAs($worker)->delete(route('animals.destroy', $animal))->assertForbidden();
        $this->assertFalse($animal->fresh()->trashed());

        // Ishchi tahrirlay oladi va tugmalar huquqqa qarab ko'rinadi
        $this->actingAs($worker)->get(route('animals.show', $animal))
            ->assertOk()
            ->assertSee(route('animals.edit', $animal))
            ->assertDontSee(route('animals.import'));
        // animals.update va animals.destroy bir xil URL — o'chirish tugmasi matni bo'yicha tekshiriladi
        $this->actingAs($worker)->get(route('animals.edit', $animal))
            ->assertOk()
            ->assertDontSee("Bu hayvonni o'chirish");
        $this->actingAs($owner)->get(route('animals.edit', $animal))
            ->assertOk()
            ->assertSee("Bu hayvonni o'chirish");
    }

    public function test_viewer_cannot_create_or_change_anything(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $animal = $this->animalIn($farm);
        $viewer = $this->addMember($farm, FarmUser::ROLE_VIEWER);

        $this->actingAs($viewer)->get(route('animals.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('animals.store'), ['name' => 'Yangi', 'direction' => 'sut'])->assertForbidden();
        $this->actingAs($viewer)->post(route('reminders.store', $animal), ['title' => 'Emlash', 'due_date' => '2026-11-10'])->assertForbidden();
        $this->actingAs($viewer)->post(route('expenses.store', $animal), ['type' => 'yem', 'amount' => 10, 'expense_date' => '2026-11-01'])->assertForbidden();
        $this->actingAs($viewer)->get(route('animals.import'))->assertForbidden();
        $this->assertSame(1, Animal::where('farm_id', $farm->id)->count());

        $this->actingAs($viewer)->get(route('animals.index'))
            ->assertOk()
            ->assertDontSee(route('animals.create'))
            ->assertDontSee(route('animals.import'));

        $this->actingAs($viewer)->get(route('animals.show', $animal))
            ->assertOk()
            ->assertDontSee(route('animals.edit', $animal))
            ->assertDontSee(route('reminders.store', $animal))
            ->assertDontSee(route('expenses.store', $animal));

        // Menyu: tariflar, xodimlar va ferma jurnali ko'rinmaydi
        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('plans.index'))
            ->assertDontSee(route('staff.index'))
            ->assertDontSee(route('farm-audit.index'))
            ->assertSee($farm->name);
        $this->actingAs($viewer)->get(route('plans.index'))->assertForbidden();
    }

    public function test_other_farm_records_return_404_for_staff(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $managerA = $this->addMember($this->farmOf($ownerA), FarmUser::ROLE_MANAGER);
        $animalB = $this->animalIn($this->farmOf($ownerB), null, ['name' => 'B sigiri']);
        $memberB = $this->addMember($this->farmOf($ownerB), FarmUser::ROLE_WORKER);
        $membershipB = FarmUser::where('user_id', $memberB->id)->firstOrFail();

        $this->actingAs($managerA)->get(route('animals.show', $animalB))->assertNotFound();
        $this->actingAs($managerA)->delete(route('animals.destroy', $animalB))->assertNotFound();

        // Egasi boshqa fermaning a'zoligini boshqara olmaydi
        $this->actingAs($ownerA)->patch(route('staff.role', $membershipB->id), ['role' => FarmUser::ROLE_VIEWER])->assertNotFound();
        $this->actingAs($ownerA)->patch(route('staff.status', $membershipB->id))->assertNotFound();
        $this->actingAs($ownerA)->delete(route('staff.destroy', $membershipB->id))->assertNotFound();
        $this->assertSame(FarmUser::ROLE_WORKER, $membershipB->fresh()->role);
        $this->assertSame(FarmUser::STATUS_ACTIVE, $membershipB->fresh()->status);

        // Ferma almashtirgich: faqat o'z a'zoligiga
        $this->actingAs($managerA)->post(route('farm.switch'), ['farm_id' => $this->farmOf($ownerB)->id])->assertNotFound();
        $this->assertSame($this->farmOf($ownerA)->id, (int) $managerA->fresh()->current_farm_id);
    }

    public function test_only_owner_manages_staff_and_manager_sees_audit_log(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);

        $this->actingAs($manager)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($manager)->post(route('staff.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'worker'])->assertForbidden();
        $this->actingAs($manager)->get(route('farm-audit.index'))->assertOk();
        $this->actingAs($worker)->get(route('farm-audit.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('farm-settings.edit'))->assertForbidden();

        $this->actingAs($owner)->get(route('staff.index'))
            ->assertOk()
            ->assertSee($manager->email)
            ->assertSee($worker->email);
    }

    public function test_owner_adds_new_staff_with_one_time_password(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'basic');

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Yangi Ishchi',
            'email' => 'Ishchi@Example.com',
            'role' => FarmUser::ROLE_WORKER,
        ])->assertRedirect(route('staff.index'));

        // Flashda parol faqat shifrlangan holda
        $encrypted = session('staff_credentials');
        $this->assertIsString($encrypted);

        $response = $this->actingAs($owner)->get(route('staff.index'));
        $response->assertOk()
            ->assertSee(__('Bu parol faqat hozir ko\'rsatiladi. Uni xodimga bering.'))
            ->assertSee('Yangi Ishchi')
            ->assertSee('ishchi@example.com');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $staff = User::where('email', 'ishchi@example.com')->firstOrFail();
        $membership = FarmUser::where('farm_id', $farm->id)->where('user_id', $staff->id)->firstOrFail();

        $this->assertSame(FarmUser::ROLE_WORKER, $membership->role);
        $this->assertTrue($membership->must_change_password);
        $this->assertSame($owner->id, (int) $membership->invited_by);
        $this->assertSame($farm->id, (int) $staff->current_farm_id);
        // Xodim uchun alohida ferma yaratilmaydi
        $this->assertSame(0, Farm::where('owner_id', $staff->id)->count());

        // Parol bir marta ko'rsatiladi, bazada faqat hash
        $password = $this->passwordFrom($response->getContent());
        $this->assertSame(10, strlen($password));
        $this->assertStringNotContainsString($password, $encrypted);
        $this->assertSame($password, json_decode(Crypt::decryptString($encrypted), true)['password']);
        $this->assertNotSame($password, $staff->password);
        $this->assertTrue(Hash::check($password, $staff->password));
        $this->assertDatabaseMissing('users', ['password' => $password]);

        // Sahifa yangilansa flash tugaydi
        $this->actingAs($owner)->get(route('staff.index'))->assertOk()
            ->assertDontSee($password)
            ->assertDontSee('stf-password');
        $this->assertNull(session('staff_credentials'));

        $this->assertDatabaseHas('farm_audit_logs', [
            'farm_id' => $farm->id,
            'user_id' => $owner->id,
            'amal' => FarmAuditLog::ACTION_STAFF_ADDED,
            'obyekt_id' => $staff->id,
        ]);
    }

    public function test_existing_account_is_attached_without_password_change(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'basic');
        $existing = User::factory()->create(['email' => 'mavjud@example.com']);
        $oldHash = $existing->password;

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Boshqa ism',
            'email' => 'mavjud@example.com',
            'role' => FarmUser::ROLE_MANAGER,
        ])->assertRedirect(route('staff.index'));
        $this->assertNull(session('staff_credentials'));
        $this->actingAs($owner)->get(route('staff.index'))->assertOk()->assertDontSee('stf-password');

        $existing->refresh();
        $this->assertSame($oldHash, $existing->password);
        $this->assertNotSame('Boshqa ism', $existing->name);
        $membership = FarmUser::where('farm_id', $farm->id)->where('user_id', $existing->id)->firstOrFail();
        $this->assertFalse($membership->must_change_password);
        // O'z fermasi saqlanadi; endi 2 ta faol a'zolik — menyuda almashtirgich chiqadi
        $this->assertSame(1, Farm::where('owner_id', $existing->id)->count());

        $this->actingAs($existing)->get(route('dashboard'))->assertOk()->assertSee(route('farm.switch'));
        $this->actingAs($existing)->post(route('farm.switch'), ['farm_id' => $farm->id])->assertRedirect(route('dashboard'));
        $this->assertSame($farm->id, (int) $existing->fresh()->current_farm_id);

        // Allaqachon a'zo — xato, ikkinchi a'zolik qo'shilmaydi
        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Takror',
            'email' => 'mavjud@example.com',
            'role' => FarmUser::ROLE_WORKER,
        ])->assertSessionHasErrors('staff');
        $this->assertSame(1, FarmUser::where('farm_id', $farm->id)->where('user_id', $existing->id)->count());
    }

    public function test_staff_is_not_added_when_limit_is_full(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);

        // Bepul tarif: 0 ta xodim
        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Ishchi',
            'email' => 'ishchi@example.com',
            'role' => FarmUser::ROLE_WORKER,
        ])->assertSessionHasErrors('staff');
        $this->assertDatabaseMissing('users', ['email' => 'ishchi@example.com']);

        // Basic: 2 ta xodim; egasi hisobga kirmaydi, to'xtatilgan xodim ham
        $this->setPlan($farm, 'basic');
        $this->addMember($farm, FarmUser::ROLE_WORKER);
        $suspended = $this->addMember($farm, FarmUser::ROLE_VIEWER, FarmUser::STATUS_SUSPENDED);

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Ikkinchi',
            'email' => 'ikkinchi@example.com',
            'role' => FarmUser::ROLE_WORKER,
        ])->assertRedirect(route('staff.index'));
        $this->assertSame(2, $farm->activeStaffCount());

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Uchinchi',
            'email' => 'uchinchi@example.com',
            'role' => FarmUser::ROLE_WORKER,
        ])->assertSessionHasErrors('staff');
        $this->assertDatabaseMissing('users', ['email' => 'uchinchi@example.com']);

        // Faollashtirishda ham limit tekshiriladi
        $membership = FarmUser::where('user_id', $suspended->id)->firstOrFail();
        $this->actingAs($owner)->patch(route('staff.status', $membership->id))->assertSessionHasErrors('staff');
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $membership->fresh()->status);
    }

    public function test_owner_cannot_change_self_or_grant_owner_role(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'basic');
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $ownerMembership = FarmUser::where('farm_id', $farm->id)->where('user_id', $owner->id)->firstOrFail();
        $workerMembership = FarmUser::where('farm_id', $farm->id)->where('user_id', $worker->id)->firstOrFail();

        $this->actingAs($owner)->patch(route('staff.role', $ownerMembership->id), ['role' => FarmUser::ROLE_VIEWER])->assertSessionHasErrors('staff');
        $this->actingAs($owner)->patch(route('staff.status', $ownerMembership->id))->assertSessionHasErrors('staff');
        $this->actingAs($owner)->delete(route('staff.destroy', $ownerMembership->id))->assertSessionHasErrors('staff');
        $this->assertSame(FarmUser::ROLE_OWNER, $ownerMembership->fresh()->role);
        $this->assertSame(FarmUser::STATUS_ACTIVE, $ownerMembership->fresh()->status);

        $this->actingAs($owner)->patch(route('staff.role', $workerMembership->id), ['role' => FarmUser::ROLE_OWNER])->assertSessionHasErrors('role');
        $this->actingAs($owner)->post(route('staff.store'), ['name' => 'E', 'email' => 'e@example.com', 'role' => FarmUser::ROLE_OWNER])->assertSessionHasErrors('role');
        $this->assertSame(FarmUser::ROLE_WORKER, $workerMembership->fresh()->role);

        // Rolni o'zgartirish, to'xtatish va chiqarish — jurnalga yoziladi
        $this->actingAs($owner)->patch(route('staff.role', $workerMembership->id), ['role' => FarmUser::ROLE_MANAGER])->assertRedirect(route('staff.index'));
        $this->assertSame(FarmUser::ROLE_MANAGER, $workerMembership->fresh()->role);
        $this->actingAs($owner)->patch(route('staff.status', $workerMembership->id))->assertRedirect(route('staff.index'));
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $workerMembership->fresh()->status);
        $this->actingAs($owner)->delete(route('staff.destroy', $workerMembership->id))->assertRedirect(route('staff.index'));

        $this->assertNull(FarmUser::find($workerMembership->id));
        // Akkaunt o'chirilmaydi
        $this->assertNotNull(User::find($worker->id));
        $this->assertNull($worker->fresh()->current_farm_id);

        foreach ([FarmAuditLog::ACTION_STAFF_ROLE_CHANGED, FarmAuditLog::ACTION_STAFF_STATUS_CHANGED, FarmAuditLog::ACTION_STAFF_REMOVED] as $action) {
            $this->assertDatabaseHas('farm_audit_logs', ['farm_id' => $farm->id, 'amal' => $action, 'obyekt_id' => $worker->id]);
        }
    }

    public function test_plan_downgrade_suspends_latest_excess_staff(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'pro');

        $first = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $this->travel(1)->minutes();
        $second = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $this->travel(1)->minutes();
        $third = $this->addMember($farm, FarmUser::ROLE_VIEWER);

        // Pro (5) -> Basic (2): eng oxirgi qo'shilgan to'xtatiladi
        $this->actingAs($admin)
            ->patch(route('admin.users.plan', $owner), ['plan_id' => Plan::where('kod', 'basic')->value('id')])
            ->assertSessionHas('success');

        $status = fn (User $user) => FarmUser::where('farm_id', $farm->id)->where('user_id', $user->id)->value('status');
        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($first));
        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($second));
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $status($third));
        $this->assertDatabaseHas('farm_audit_logs', ['farm_id' => $farm->id, 'user_id' => null, 'amal' => FarmAuditLog::ACTION_STAFF_AUTO_SUSPENDED, 'obyekt_id' => $third->id]);

        // Egasiga ogohlantirish
        $this->actingAs($owner)->get(route('staff.index'))->assertOk()->assertSee(__(':count ta xodim to\'xtatilgan: tarifingizdagi xodimlar limiti to\'lgan. Ularni faollashtirish uchun yuqoriroq tarifga o\'ting yoki boshqa xodimni to\'xtating.', ['count' => 1]));

        // Admin tarifdagi max_staff ni kamaytirsa (Basic 2 -> 1), shu tarifdagi fermalarda ham
        $basic = Plan::where('kod', 'basic')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.plans.update', $basic), [
            'nomi' => $basic->nomi,
            'narx_som' => $basic->narx_som,
            'max_hayvon' => $basic->max_hayvon,
            'max_staff' => 1,
            'oylik_chat_limit' => $basic->oylik_chat_limit,
            'oylik_vision_limit' => $basic->oylik_vision_limit,
            'chat_model' => $basic->aiModel('chat'),
            'vision_model' => $basic->aiModel('vision'),
            'faol' => 1,
        ])->assertSessionHas('success');

        $this->assertSame(FarmUser::STATUS_ACTIVE, $status($first));
        $this->assertSame(FarmUser::STATUS_SUSPENDED, $status($second));
        $this->assertSame(1, $farm->fresh()->activeStaffCount());

        // Admin ro'yxatida: ferma nomi, rol va a'zolar soni (ega + 1 faol xodim)
        $this->actingAs($admin)->get(route('admin.users.index', ['q' => $owner->email]))
            ->assertOk()
            ->assertSee($farm->name)
            ->assertSee(__("A'zolar") . ': 2');
    }

    public function test_must_change_password_is_enforced(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'basic');

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Yangi',
            'email' => 'yangi@example.com',
            'role' => FarmUser::ROLE_WORKER,
        ]);
        $temporary = $this->passwordFrom($this->actingAs($owner)->get(route('staff.index'))->getContent());

        $staff = User::where('email', 'yangi@example.com')->firstOrFail();

        $this->actingAs($staff)->get(route('dashboard'))->assertRedirect(route('password.force'));
        $this->actingAs($staff)->get(route('animals.index'))->assertRedirect(route('password.force'));
        $this->actingAs($staff)->get('/profile')->assertRedirect(route('password.force'));
        $this->actingAs($staff)->getJson(route('animals.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('password.force'))->assertOk();

        // Vaqtinchalik parolni takrorlab bo'lmaydi
        $this->actingAs($staff)->put(route('password.force.update'), [
            'current_password' => $temporary,
            'password' => $temporary,
            'password_confirmation' => $temporary,
        ])->assertSessionHasErrors('password');

        $this->actingAs($staff)->put(route('password.force.update'), [
            'current_password' => $temporary,
            'password' => 'YangiParol123',
            'password_confirmation' => 'YangiParol123',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse(FarmUser::where('user_id', $staff->id)->firstOrFail()->must_change_password);
        $this->assertTrue(Hash::check('YangiParol123', $staff->fresh()->password));
        $this->actingAs($staff->fresh())->get(route('animals.index'))->assertOk();

        // Chiqish har doim ishlaydi
        $other = $this->addMember($farm, FarmUser::ROLE_VIEWER);
        FarmUser::where('user_id', $other->id)->update(['must_change_password' => true]);
        $this->actingAs($other)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_owner_regenerates_temporary_password_for_staff_created_here(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'basic');

        $this->actingAs($owner)->post(route('staff.store'), [
            'name' => 'Unutuvchan',
            'email' => 'unutdi@example.com',
            'role' => FarmUser::ROLE_WORKER,
        ]);
        $first = $this->passwordFrom($this->actingAs($owner)->get(route('staff.index'))->getContent());

        $staff = User::where('email', 'unutdi@example.com')->firstOrFail();
        $membership = FarmUser::where('farm_id', $farm->id)->where('user_id', $staff->id)->firstOrFail();

        $this->actingAs($owner)->get(route('staff.index'))->assertOk()
            ->assertSee(route('staff.password', $membership->id))
            ->assertSee(__('Yangi vaqtinchalik parol'));

        $this->actingAs($owner)->post(route('staff.password', $membership->id))->assertRedirect(route('staff.index'));
        $encrypted = session('staff_credentials');

        $response = $this->actingAs($owner)->get(route('staff.index'));
        $response->assertOk()->assertSee('unutdi@example.com');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $second = $this->passwordFrom($response->getContent());

        $this->assertSame(10, strlen($second));
        $this->assertNotSame($first, $second);
        $this->assertStringNotContainsString($second, $encrypted);
        $staff->refresh();
        $this->assertTrue(Hash::check($second, $staff->password));
        $this->assertFalse(Hash::check($first, $staff->password));
        $this->assertTrue($membership->fresh()->must_change_password);

        $this->actingAs($owner)->get(route('staff.index'))->assertDontSee($second);

        // Jurnalga yoziladi, parolning o'zi yozilmaydi
        $log = FarmAuditLog::where('farm_id', $farm->id)->where('amal', FarmAuditLog::ACTION_STAFF_PASSWORD_RESET)->firstOrFail();
        $this->assertSame($owner->id, (int) $log->user_id);
        $this->assertSame($staff->id, (int) $log->obyekt_id);
        $this->assertStringNotContainsString($second, json_encode([$log->eski_qiymat, $log->yangi_qiymat]));

        // Taklif qilgan o'chgan (invited_by NULL), lekin akkaunt shu fermada yaratilgan — ruxsat
        $membership->forceFill(['invited_by' => null])->save();
        $this->actingAs($owner)->post(route('staff.password', $membership->id))->assertRedirect(route('staff.index'));

        // Xodim parolni almashtirgach — endi mumkin emas
        $membership->forceFill(['must_change_password' => false])->save();
        $this->actingAs($owner)->get(route('staff.index'))->assertDontSee(route('staff.password', $membership->id));
        $this->actingAs($owner)->post(route('staff.password', $membership->id))->assertForbidden();
    }

    public function test_temporary_password_reset_is_forbidden_for_other_members(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'pro');

        // Mavjud akkaunt (o'z fermasi bor), egasi taklif qilmagan
        $existing = User::factory()->create();
        $farm->members()->attach($existing->id, ['role' => FarmUser::ROLE_WORKER, 'status' => FarmUser::STATUS_ACTIVE, 'must_change_password' => true]);
        $existingMembership = FarmUser::where('farm_id', $farm->id)->where('user_id', $existing->id)->firstOrFail();
        $oldHash = $existing->password;

        // Akkaunt boshqa fermada yaratilgan (birinchi a'zoligi o'sha yerda)
        $otherOwner = User::factory()->create();
        $foreign = $this->addMember($this->farmOf($otherOwner), FarmUser::ROLE_WORKER);
        $farm->members()->attach($foreign->id, ['role' => FarmUser::ROLE_WORKER, 'status' => FarmUser::STATUS_ACTIVE, 'must_change_password' => true]);
        $foreignMembership = FarmUser::where('farm_id', $farm->id)->where('user_id', $foreign->id)->firstOrFail();

        // Egasi taklif qilgan, lekin parolni allaqachon almashtirgan
        $changed = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $changedMembership = FarmUser::where('farm_id', $farm->id)->where('user_id', $changed->id)->firstOrFail();
        $changedMembership->forceFill(['invited_by' => $owner->id])->save();

        $ownerMembership = FarmUser::where('farm_id', $farm->id)->where('user_id', $owner->id)->firstOrFail();

        $forbidden = [$existingMembership, $foreignMembership, $changedMembership, $ownerMembership];

        foreach ($forbidden as $membership) {
            $this->actingAs($owner)->post(route('staff.password', $membership->id))->assertForbidden();
        }

        $page = $this->actingAs($owner)->get(route('staff.index'))->assertOk();

        foreach ($forbidden as $membership) {
            $page->assertDontSee(route('staff.password', $membership->id));
        }

        $this->assertSame($oldHash, $existing->fresh()->password);
        $this->assertNull(session('staff_credentials'));

        // staff.manage huquqi yo'q (menejer) — 403
        $manager = $this->addMember($farm, FarmUser::ROLE_MANAGER);
        $this->actingAs($owner)->post(route('staff.store'), ['name' => 'Yangi', 'email' => 'yangi2@example.com', 'role' => FarmUser::ROLE_WORKER]);
        $created = FarmUser::where('farm_id', $farm->id)->where('user_id', User::where('email', 'yangi2@example.com')->value('id'))->firstOrFail();
        $this->actingAs($manager)->post(route('staff.password', $created->id))->assertForbidden();

        // Boshqa fermaning a'zosi — 404
        $this->actingAs($otherOwner)->post(route('staff.password', $created->id))->assertNotFound();

        $this->assertSame(0, FarmAuditLog::where('amal', FarmAuditLog::ACTION_STAFF_PASSWORD_RESET)->count());
    }

    public function test_suspended_member_cannot_access_farm_or_bot(): void
    {
        config(['services.telegram.webhook_secret' => 'secret', 'services.telegram.bot_token' => 'token']);
        Http::fake();

        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->animalIn($farm, null, ['name' => 'Yashirin sigir']);
        $suspended = $this->addMember($farm, FarmUser::ROLE_MANAGER, FarmUser::STATUS_SUSPENDED);
        $suspended->forceFill(['telegram_chat_id' => '777'])->save();

        $this->actingAs($suspended)->get(route('animals.index'))->assertForbidden();
        $this->actingAs($suspended)->get(route('farm-audit.index'))->assertForbidden();
        $this->actingAs($suspended)->post(route('farm.switch'), ['farm_id' => $farm->id])->assertNotFound();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret')
            ->postJson('/telegram/webhook', ['message' => ['text' => '/hayvonlarim', 'chat' => ['id' => 777]]])
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', __("Siz hech qaysi fermaga a'zo emassiz yoki a'zoligingiz to'xtatilgan. Ferma egasiga yoki administratsiyaga murojaat qiling.", [], 'ru')));
        Http::assertNotSent(fn ($request) => str_contains($request['text'] ?? '', 'Yashirin sigir'));
    }

    public function test_bot_respects_roles(): void
    {
        config(['services.telegram.webhook_secret' => 'secret', 'services.telegram.bot_token' => 'token']);
        Http::fake();

        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->animalIn($farm, null, ['name' => 'Oq sigir']);
        $viewer = $this->addMember($farm, FarmUser::ROLE_VIEWER);
        $viewer->forceFill(['telegram_chat_id' => '888'])->save();

        $send = fn (string $text) => $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret')
            ->postJson('/telegram/webhook', ['message' => ['text' => $text, 'chat' => ['id' => 888]]])
            ->assertOk();

        // Ko'rish mumkin
        $send('/hayvonlarim');
        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', 'Oq sigir'));

        // Kuzatuvchilarni boshqarish — huquq yo'q, 3 tilda javob
        $send('/kuzatuvchilar');
        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', "Bu amal uchun huquqingiz yo'q.")
            && str_contains($request['text'] ?? '', __("Bu amal uchun huquqingiz yo'q.", [], 'ru'))
            && str_contains($request['text'] ?? '', __("Bu amal uchun huquqingiz yo'q.", [], 'uz_cyrl')));
    }

    public function test_telegram_chat_is_linked_to_one_account_only(): void
    {
        config(['services.telegram.webhook_secret' => 'secret', 'services.telegram.bot_token' => 'token']);
        Http::fake();

        $owner = User::factory()->create();
        $owner->forceFill(['telegram_chat_id' => '999'])->save();
        $worker = $this->addMember($this->farmOf($owner), FarmUser::ROLE_WORKER);

        $code = $this->actingAs($worker)->postJson(route('telegram.generate-code'))->assertOk()->json('code');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret')
            ->postJson('/telegram/webhook', ['message' => ['text' => $code, 'chat' => ['id' => 999]]])
            ->assertOk();

        $this->assertSame('999', (string) $worker->fresh()->telegram_chat_id);
        $this->assertNull($owner->fresh()->telegram_chat_id);
    }

    public function test_staff_account_deletion_keeps_animals_but_owner_deletion_removes_farm(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);

        $animal = $this->animalIn($farm, $worker, ['name' => 'Ishchi qoshgan sigir']);

        // Xodim o'z akkauntini o'chiradi — hayvon fermada qoladi, user_id NULL
        $this->actingAs($worker)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertNull(User::find($worker->id));

        $animal = Animal::find($animal->id);
        $this->assertNotNull($animal);
        $this->assertNull($animal->user_id);
        $this->assertSame($farm->id, (int) $animal->farm_id);

        $this->actingAs($owner)->get(route('animals.show', $animal))
            ->assertOk()
            ->assertSee("O'chirilgan foydalanuvchi");

        // Ega akkauntini o'chirsa — ferma va uning ma'lumotlari o'chadi (hozirgi xatti-harakat)
        $this->actingAs($owner)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertNull(Farm::find($farm->id));
        $this->assertNull(Animal::withTrashed()->find($animal->id));
    }

    public function test_farm_deletion_keeps_ai_usage_logs_with_null_farm(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $log = AiUsageLog::create([
            'farm_id' => $farm->id,
            'user_id' => $owner->id,
            'turi' => AiUsageLog::TYPE_CHAT,
            'model' => 'gpt-4o-mini',
            'narx_usd' => 0.0125,
        ]);

        $farm->delete();

        // Platformaning AI xarajat tarixi saqlanadi
        $log = AiUsageLog::find($log->id);
        $this->assertNotNull($log);
        $this->assertNull($log->farm_id);
        $this->assertSame('0.012500', (string) $log->narx_usd);
    }

    public function test_farm_audit_log_records_actions_and_filters(): void
    {
        $owner = User::factory()->create();
        $farm = $this->farmOf($owner);
        $this->setPlan($farm, 'basic');
        $worker = $this->addMember($farm, FarmUser::ROLE_WORKER);
        $other = User::factory()->create();

        $this->actingAs($worker)->post(route('animals.store'), ['name' => 'Jurnal sigiri', 'direction' => 'sut', 'initial_weight' => '450'])
            ->assertRedirect(route('animals.index'));
        $animal = Animal::where('name', 'Jurnal sigiri')->firstOrFail();

        // O'zgarmagan vazn ("450" va bazadagi 450.00) jurnalga o'zgarish sifatida tushmaydi
        $this->actingAs($worker)->put(route('animals.update', $animal), ['name' => 'Jurnal sigiri 2', 'direction' => 'sut', 'initial_weight' => '450', 'current_weight' => '450']);
        $this->actingAs($worker)->post(route('reminders.store', $animal), ['title' => 'Emlash', 'due_date' => '2026-11-10']);
        $this->actingAs($worker)->post(route('expenses.store', $animal), ['type' => 'yem', 'amount' => 1000, 'expense_date' => '2026-11-01']);
        $this->actingAs($worker)->post(route('incomes.store', $animal), ['type' => 'sut', 'amount' => 2000, 'income_date' => '2026-11-01']);
        $this->actingAs($owner)->delete(route('animals.destroy', $animal));
        $this->actingAs($owner)->post(route('animals.restore', $animal->id));
        $this->actingAs($owner)->patch(route('farm-settings.update'), ['name' => 'Yangi ferma nomi'])->assertRedirect(route('farm-settings.edit'));

        // Boshqa fermadagi amal bu jurnalga tushmaydi
        $this->actingAs($other)->post(route('animals.store'), ['name' => 'Begona', 'direction' => 'sut']);

        $logs = FarmAuditLog::where('farm_id', $farm->id)->orderBy('id')->get();
        $this->assertSame([
            FarmAuditLog::ACTION_ANIMAL_CREATED,
            FarmAuditLog::ACTION_ANIMAL_UPDATED,
            FarmAuditLog::ACTION_REMINDER_CREATED,
            FarmAuditLog::ACTION_EXPENSE_CREATED,
            FarmAuditLog::ACTION_INCOME_CREATED,
            FarmAuditLog::ACTION_ANIMAL_DELETED,
            FarmAuditLog::ACTION_ANIMAL_RESTORED,
            FarmAuditLog::ACTION_FARM_RENAMED,
        ], $logs->pluck('amal')->all());

        $update = $logs->firstWhere('amal', FarmAuditLog::ACTION_ANIMAL_UPDATED);
        $this->assertSame(['name' => 'Jurnal sigiri'], $update->eski_qiymat);
        $this->assertSame(['name' => 'Jurnal sigiri 2'], $update->yangi_qiymat);
        $this->assertSame($worker->id, (int) $update->user_id);
        $this->assertSame('Yangi ferma nomi', $farm->fresh()->name);

        $this->actingAs($owner)->get(route('farm-audit.index'))
            ->assertOk()
            ->assertSee('Jurnal sigiri 2')
            ->assertDontSee('Begona');

        // Filtr: xodim va amal bo'yicha
        $this->actingAs($owner)->get(route('farm-audit.index', ['user' => $owner->id]))
            ->assertOk()
            ->assertSee('Yangi ferma nomi')
            ->assertDontSee('Emlash');
        $this->actingAs($owner)->get(route('farm-audit.index', ['amal' => FarmAuditLog::ACTION_EXPENSE_CREATED]))
            ->assertOk()
            ->assertSee('yem')
            ->assertDontSee('Emlash');
    }
}
