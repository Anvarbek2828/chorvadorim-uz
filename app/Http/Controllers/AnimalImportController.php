<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\AnimalImportService;
use App\Services\SpreadsheetReader;
use App\Services\SpreadsheetReaderException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Hayvonlarni fayldan yuklash: 1) fayl o'qiladi va tekshiriladi (bazaga hech narsa yozilmaydi),
 * 2) "Tasdiqlash"da faqat to'g'ri qatorlar qo'shiladi.
 *
 * Bir xil amallar ikki joyda ishlatiladi: /animals/import (joriy fermaga) va
 * /admin/users/{user}/import (admin foydalanuvchi nomidan uning fermasiga, audit jurnaliga yoziladi).
 * Shuning uchun {user} va {token} marshrut parametrlari $request->route() orqali olinadi.
 */
class AnimalImportController extends Controller
{
    // O'qilgan qatorlar tasdiqlashgacha keshda saqlanadi; yuklangan fayl esa darhol o'chiriladi
    private const CACHE_MINUTES = 120;

    public function __construct(private AnimalImportService $import)
    {
    }

    public function create(Request $request)
    {
        $context = $this->context($request);
        $farm = $context['farm'];

        return view('animals.import', $context + [
            'xlsxSupported' => SpreadsheetReader::xlsxSupported(),
            'maxAnimals' => $farm->activePlan()->max_hayvon,
            'currentAnimals' => $farm->animals()->count(),
            'uploadUrl' => $this->url($context, 'upload'),
        ]);
    }

    public function template()
    {
        return $this->csvDownload($this->import->templateCsv(), 'hayvonlar-shablon.csv');
    }

    public function upload(Request $request)
    {
        $context = $this->context($request);

        $request->validate([
            'file' => ['required', 'file', 'max:' . AnimalImportService::MAX_FILE_KB],
        ], [
            'file.required' => __('Faylni tanlang.'),
            'file.file' => __('Faylni yuklab bo\'lmadi. Qayta urinib ko\'ring.'),
            'file.uploaded' => __('Faylni yuklab bo\'lmadi. Fayl hajmi 2 MB dan oshmasligi kerak.'),
            'file.max' => __('Fayl hajmi 2 MB dan oshmasligi kerak.'),
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if (!in_array($extension, ['xlsx', 'csv'], true)) {
                throw new SpreadsheetReaderException(__("Fayl turi noto'g'ri. Faqat .xlsx yoki .csv fayl yuklang."));
            }

            $rows = $this->import->parse($file->getRealPath(), $extension);
        } catch (SpreadsheetReaderException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } finally {
            // Fayl faqat PHP'ning vaqtinchalik papkasida edi; o'qilgach saqlanmaydi
            @unlink($file->getRealPath());
        }

        $token = Str::random(40);

        Cache::put($this->cacheKey($token), [
            'actor_id' => $request->user()->id,
            'user_id' => $context['target']->id,
            'farm_id' => $context['farm']->id,
            'file_name' => Str::limit(basename($file->getClientOriginalName()), 100),
            'rows' => $rows,
            'result' => null,
        ], now()->addMinutes(self::CACHE_MINUTES));

        return redirect($this->url($context, 'preview', $token));
    }

    public function preview(Request $request)
    {
        $context = $this->context($request);
        [$token, $import] = $this->load($request, $context);

        // Tasdiqlanmaguncha har safar joriy baza holati bo'yicha qayta tekshiriladi
        $result = $import['result'] ?? $this->import->validate($context['farm'], $import['rows']);
        $onlyErrors = $request->query('show') === 'errors';

        $rows = array_map(fn ($row) => $row + ['messages' => $this->import->messages($row)], $result['rows']);

        if ($onlyErrors) {
            $rows = array_filter($rows, fn ($row) => $row['status'] !== AnimalImportService::STATUS_OK);
        }

        return view('animals.import-preview', $context + [
            'rows' => $rows,
            'summary' => $result['summary'],
            'done' => $import['result'] !== null,
            'added' => $result['added'] ?? 0,
            'fileName' => $import['file_name'],
            'onlyErrors' => $onlyErrors,
            'labels' => $this->import->fieldLabels(),
            'urls' => [
                'confirm' => $this->url($context, 'confirm', $token),
                'errors' => $this->url($context, 'errors', $token),
                'cancel' => $this->url($context, 'cancel', $token),
                'preview' => $this->url($context, 'preview', $token),
                'restart' => $this->url($context, 'create'),
            ],
        ]);
    }

    public function confirm(Request $request, AdminAuditService $audit)
    {
        $context = $this->context($request);
        [$token, $import] = $this->load($request, $context);
        $previewUrl = $this->url($context, 'preview', $token);

        // Ikki marta bosilganda bir xil qatorlar ikki marta qo'shilmasligi uchun
        $lock = Cache::lock($this->cacheKey($token) . ':lock', 60);

        if (!$lock->get()) {
            return redirect($previewUrl)->with('error', __('Import bajarilmoqda, biroz kuting.'));
        }

        try {
            $import = Cache::get($this->cacheKey($token)) ?? $import;

            if ($import['result'] !== null) {
                return redirect($previewUrl)->with('error', __('Bu import allaqachon tasdiqlangan.'));
            }

            try {
                $result = $this->import->import($context['farm'], $context['target'], $import['rows']);
            } catch (QueryException $e) {
                report($e);

                return redirect($previewUrl)->with('error', __("Saqlashda xato yuz berdi, hech narsa qo'shilmadi. Qayta urinib ko'ring."));
            }

            $import['result'] = $result;
            Cache::put($this->cacheKey($token), $import, now()->addMinutes(self::CACHE_MINUTES));
        } finally {
            $lock->release();
        }

        $summary = $result['summary'];

        if ($context['adminMode']) {
            $audit->log(AdminAuditLog::ACTION_USER_ANIMALS_IMPORTED, 'user', $context['target']->id, [], [
                'fayl' => $import['file_name'],
                'qatorlar' => $summary['total'],
                'qoshildi' => $result['added'],
                'xato' => $summary['error'],
                'limit' => $summary['limit'],
            ], null, null, $context['farm']->id);
        }

        $message = __(":added ta hayvon qo'shildi, :errors ta qator xato.", ['added' => $result['added'], 'errors' => $summary['error']]);

        if ($summary['limit'] > 0) {
            $message .= ' ' . __(":count ta qator tarif limiti tufayli qo'shilmadi.", ['count' => $summary['limit']]);
        }

        return redirect($previewUrl)->with('success', $message);
    }

    public function errors(Request $request)
    {
        $context = $this->context($request);
        [, $import] = $this->load($request, $context);

        $result = $import['result'] ?? $this->import->validate($context['farm'], $import['rows']);

        return $this->csvDownload($this->import->errorsCsv($result['rows']), 'hayvonlar-xatolar.csv');
    }

    public function cancel(Request $request)
    {
        $context = $this->context($request);
        [$token] = $this->load($request, $context);

        Cache::forget($this->cacheKey($token));

        return redirect($this->url($context, 'create'));
    }

    /**
     * Kimga (qaysi fermaga) yuklanmoqda va qaysi marshrutlar ishlatiladi.
     * 'target' — hayvonlarning user_id si (yaratgan), 'farm' — egasi.
     */
    private function context(Request $request): array
    {
        $userId = $request->route('user');

        if ($userId === null) {
            return [
                'target' => $request->user(),
                'farm' => $this->currentFarm(),
                'adminMode' => false,
                'routePrefix' => 'animals.import',
                'routeParams' => [],
            ];
        }

        // Admin marshrutlari 'admin' middleware ortida; bu qo'shimcha himoya
        abort_unless($request->user()?->is_admin, 404);

        $target = User::findOrFail($userId);

        // Admin foydalanuvchining o'z fermasiga (u egasi bo'lgan fermaga) yuklaydi
        $farm = $target->ownedFarms()->orderBy('id')->first();

        if (!$farm) {
            abort(redirect()->route('admin.users.index', ['q' => $target->email])->with('error', __("Bu foydalanuvchining o'z fermasi yo'q.")));
        }

        return [
            'target' => $target,
            'farm' => $farm,
            'adminMode' => true,
            'routePrefix' => 'admin.users.import',
            'routeParams' => ['user' => $target->id],
        ];
    }

    /**
     * Keshdagi importni oladi: faqat uni yuklagan kishi va faqat o'sha foydalanuvchi uchun.
     */
    private function load(Request $request, array $context): array
    {
        $token = (string) $request->route('token');
        $import = Cache::get($this->cacheKey($token));

        if (!$import || $import['actor_id'] !== $request->user()->id || $import['user_id'] !== $context['target']->id
            || ($import['farm_id'] ?? null) !== $context['farm']->id) {
            abort(redirect($this->url($context, 'create'))->with('error', __('Import topilmadi yoki muddati tugagan. Faylni qayta yuklang.')));
        }

        return [$token, $import];
    }

    private function url(array $context, string $action, ?string $token = null): string
    {
        $name = $action === 'create' ? $context['routePrefix'] : $context['routePrefix'] . '.' . $action;

        return route($name, $context['routeParams'] + ($token !== null ? ['token' => $token] : []));
    }

    private function cacheKey(string $token): string
    {
        return 'animal-import:' . $token;
    }

    private function csvDownload(string $content, string $fileName)
    {
        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
