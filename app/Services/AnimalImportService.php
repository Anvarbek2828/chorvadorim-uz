<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hayvonlarni .xlsx/.csv fayldan ommaviy qo'shish: faylni o'qish, qatorlarni tekshirish va saqlash.
 * Tekshiruv bazaga hech narsa yozmaydi; saqlash faqat import() da, bitta tranzaksiyada.
 */
class AnimalImportService
{
    public const MAX_ROWS = 1000;
    public const MAX_FILE_KB = 2048;

    public const STATUS_OK = 'ok';
    public const STATUS_ERROR = 'error';
    public const STATUS_LIMIT = 'limit';

    /**
     * Ustunlar: shablondagi sarlavha (o'zbek lotinida) va qabul qilinadigan muqobil nomlar (kirill, rus).
     */
    public const FIELDS = [
        'name' => ['label' => 'Nomi', 'required' => true, 'aliases' => ['hayvon nomi', 'name', 'номи', 'название', 'кличка', 'имя']],
        'direction' => ['label' => "Yo'nalish", 'required' => true, 'aliases' => ['direction', 'йўналиш', 'направление']],
        'breed' => ['label' => 'Zoti', 'aliases' => ['zot', 'breed', 'зоти', 'порода']],
        'birth_date' => ['label' => "Tug'ilgan sana", 'aliases' => ["tug'ilgan sanasi", 'birth date', 'туғилган сана', 'туғилган санаси', 'дата рождения']],
        'initial_weight' => ['label' => "Boshlang'ich vazn (kg)", 'aliases' => ["boshlang'ich vazni", 'vazn', 'vazni', 'weight', 'бошланғич вазн', 'вазн', 'вес', 'начальный вес']],
        'gender' => ['label' => 'Jinsi', 'aliases' => ['jins', 'gender', 'жинси', 'пол']],
        'has_passport' => ['label' => 'Pasport bor', 'aliases' => ['pasport', 'pasport bormi', 'паспорт бор', 'паспорт', 'есть паспорт']],
        'passport_number' => ['label' => 'Pasport raqami', 'aliases' => ['passport number', 'паспорт рақами', 'номер паспорта']],
        'chip_number' => ['label' => 'Chip raqami', 'aliases' => ['chip/teg raqami', 'chip', 'teg raqami', 'чип рақами', 'номер чипа', 'чип']],
        'passport_issue_date' => ['label' => 'Pasport berilgan sana', 'aliases' => ['pasport sanasi', 'паспорт берилган сана', 'дата выдачи паспорта']],
        'notes' => ['label' => 'Izoh', 'aliases' => ["qo'shimcha izoh", 'notes', 'изоҳ', 'примечание', 'комментарий']],
    ];

    // Ruxsat etilgan qiymat => qabul qilinadigan yozilishlar
    public const DIRECTIONS = [
        'sut' => ['sut', 'сут', 'молоко', 'молочное', 'молочный'],
        'gosht' => ['gosht', "go'sht", 'гўшт', 'гошт', 'мясо', 'мясное', 'мясной'],
        'nasl' => ['nasl', 'насл', 'порода (разведение)', 'разведение', 'племенное', 'племенной'],
    ];

    public const GENDERS = [
        'urgochi' => ['urgochi', "urg'ochi", 'урғочи', 'ургочи', 'самка', 'женский'],
        'erkak' => ['erkak', 'эркак', 'самец', 'мужской'],
    ];

    private const BOOLEANS = [
        'ha', 'bor', '1', 'true', 'yes', 'ҳа', 'ха', 'бор', 'да', 'есть', 'истина',
    ];

    private const BOOLEANS_FALSE = [
        "yo'q", 'yoq', '0', 'false', 'no', 'йўқ', 'йук', 'нет', 'ложь',
    ];

    private const MAX_WEIGHT = 999999.99;
    private const MAX_TEXT = 255;
    private const MAX_NOTES = 5000;

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    /**
     * Faylni o'qib, qatorlarni ustun nomlari bo'yicha ajratadi. Bazaga murojaat qilmaydi.
     *
     * @return array<int, array{line: int, raw: array<string, string>}>
     */
    public function parse(string $path, string $extension): array
    {
        $rows = $this->reader->read($path, $extension, self::MAX_ROWS);

        if (count($rows) < 2) {
            throw new SpreadsheetReaderException(__("Faylda ma'lumot topilmadi. Birinchi qatorda ustun nomlari, keyingi qatorlarda hayvonlar bo'lishi kerak."));
        }

        // array_shift() raqamli kalitlarni (fayldagi qator raqamlarini) qayta tartiblab yuboradi
        $headerLine = array_key_first($rows);
        $columns = $this->mapColumns($rows[$headerLine]);
        unset($rows[$headerLine]);

        $missing = array_diff(
            array_keys(array_filter(self::FIELDS, fn ($field) => $field['required'] ?? false)),
            array_values($columns)
        );

        if ($missing !== []) {
            throw new SpreadsheetReaderException(__('Majburiy ustunlar topilmadi: :columns. Birinchi qatorda shablondagi ustun nomlari bo\'lishi kerak.', [
                'columns' => implode(', ', array_map(fn ($field) => self::FIELDS[$field]['label'], $missing)),
            ]));
        }

        $result = [];

        foreach ($rows as $line => $cells) {
            $raw = array_fill_keys(array_keys(self::FIELDS), '');

            foreach ($columns as $index => $field) {
                $raw[$field] = $this->unescapeFormula($cells[$index] ?? '');
            }

            $result[] = ['line' => $line, 'raw' => $raw];
        }

        return $result;
    }

    /**
     * Qatorlarni joriy baza holati bo'yicha tekshiradi va har biriga holat beradi.
     * Tarif limitidan oshgan to'g'ri qatorlar STATUS_LIMIT oladi (fayldagi tartib bo'yicha).
     *
     * @return array{rows: array, summary: array{total: int, ok: int, error: int, limit: int, max_animals: int, current_animals: int}}
     */
    public function validate(User $user, array $rows): array
    {
        $firstLineByPassport = [];
        $passports = [];

        foreach ($rows as $i => $row) {
            [$data, $errors] = $this->validateRow($row['raw']);

            if ($data['passport_number'] !== null) {
                $key = $this->passportKey($data['passport_number']);

                if (isset($firstLineByPassport[$key])) {
                    $errors[] = ['code' => 'duplicate_file', 'field' => 'passport_number', 'params' => ['line' => $firstLineByPassport[$key]]];
                } else {
                    $firstLineByPassport[$key] = $row['line'];
                    $passports[] = $data['passport_number'];
                }
            }

            $rows[$i]['data'] = $data;
            $rows[$i]['errors'] = $errors;
        }

        // Unique indeks arxivdagi (soft-delete) hayvonlarni ham qamrab oladi, shuning uchun withTrashed
        $existingPassports = [];

        foreach (array_chunk($passports, 500) as $chunk) {
            Animal::withTrashed()
                ->whereIn('passport_number', $chunk)
                ->pluck('passport_number')
                ->each(function ($number) use (&$existingPassports) {
                    $existingPassports[$this->passportKey($number)] = true;
                });
        }

        $maxAnimals = $user->activePlan()->max_hayvon;
        $currentAnimals = Animal::where('user_id', $user->id)->count();
        $available = max(0, $maxAnimals - $currentAnimals);
        $summary = ['total' => count($rows), 'ok' => 0, 'error' => 0, 'limit' => 0, 'max_animals' => $maxAnimals, 'current_animals' => $currentAnimals];

        foreach ($rows as $i => $row) {
            $passport = $row['data']['passport_number'];

            if ($passport !== null && isset($existingPassports[$this->passportKey($passport)])) {
                $rows[$i]['errors'][] = ['code' => 'duplicate_db', 'field' => 'passport_number', 'params' => []];
            }

            if ($rows[$i]['errors'] !== []) {
                $status = self::STATUS_ERROR;
            } elseif ($available > 0) {
                $status = self::STATUS_OK;
                $available--;
            } else {
                $status = self::STATUS_LIMIT;
                $rows[$i]['errors'][] = ['code' => 'limit', 'field' => null, 'params' => []];
            }

            $rows[$i]['status'] = $status;
            $summary[$status]++;
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Qatorlarni qayta tekshirib (baza holati o'zgargan bo'lishi mumkin), faqat to'g'rilarini
     * bitta tranzaksiyada qo'shadi. Natijada 'added' — qo'shilganlar soni.
     */
    public function import(User $user, array $rows): array
    {
        return DB::transaction(function () use ($user, $rows) {
            // Bir foydalanuvchiga parallel importlar limitni chetlab o'tmasligi uchun
            User::whereKey($user->id)->lockForUpdate()->first();

            $result = $this->validate($user, $rows);
            $added = 0;

            foreach ($result['rows'] as $row) {
                if ($row['status'] === self::STATUS_OK) {
                    $user->animals()->create($row['data']);
                    $added++;
                }
            }

            $result['added'] = $added;

            return $result;
        });
    }

    /**
     * Qator xatolarining joriy tildagi matnlari.
     *
     * @return array<int, string>
     */
    public function messages(array $row): array
    {
        return array_map(function (array $error) {
            $params = $error['params'] + ['field' => $error['field'] ? __(self::FIELDS[$error['field']]['label']) : ''];

            return match ($error['code']) {
                'required' => __("Majburiy maydon to'ldirilmagan: :field", $params),
                'date' => __("Sana noto'g'ri: :field. Kun.oy.yil (15.03.2023), yil-oy-kun (2023-03-15) yoki Excel sanasi bo'lishi kerak.", $params),
                'date_future' => __("Sana kelajakda bo'lishi mumkin emas: :field", $params),
                'number' => __("Raqam noto'g'ri: :field. Masalan: 450 yoki 450,5", $params),
                'number_range' => __(':field 0 dan katta va :max dan oshmasligi kerak.', $params),
                'allowed' => __("Ruxsat etilmagan qiymat: :field = \":value\". Mumkin: :allowed", $params),
                'too_long' => __(':field juda uzun (ko\'pi bilan :max belgi).', $params),
                'duplicate_file' => __('Bu pasport raqami faylning :line-qatorida ham bor.', $params),
                'duplicate_db' => __('Bu pasport raqamiga ega hayvon tizimda allaqachon mavjud.'),
                'scientific' => __(":field: Excel raqamni qisqartirgan. Excel'da shu ustunni belgilab, 'Формат ячеек → Текстовый' qiling va raqamni qayta yozing.", $params),
                'limit' => __("Tarif limiti tufayli qo'shilmaydi."),
                default => $error['code'],
            };
        }, $row['errors']);
    }

    /**
     * Excel'da to'g'ri ochilishi uchun UTF-8 BOM va ";" ajratgichli shablon, 2 ta namuna qator bilan.
     */
    public function templateCsv(): string
    {
        return $this->csv([
            ['Malika', 'sut', 'Golshtin', '15.03.2023', '420', "urg'ochi", 'ha', 'NAMUNA-0001', '982000123456789', '20.04.2023', 'Kuniga 18 litr sut beradi'],
            ['Bahodir', 'gosht', 'Qozoq oqbosh', '2024-01-10', '250,5', 'erkak', "yo'q", '', '', '', ''],
        ]);
    }

    /**
     * Qo'shilmagan qatorlar — tuzatib qayta yuklash uchun. Oxirgi ustunda xato sababi.
     */
    public function errorsCsv(array $rows): string
    {
        $lines = [];

        foreach ($rows as $row) {
            if ($row['status'] !== self::STATUS_OK) {
                $lines[] = [...array_values($row['raw']), implode(' ', $this->messages($row))];
            }
        }

        return $this->csv($lines, [__('Xato sababi')]);
    }

    public function fieldLabels(): array
    {
        return array_map(fn ($field) => $field['label'], self::FIELDS);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array>}
     */
    private function validateRow(array $raw): array
    {
        $errors = [];
        $error = function (string $code, string $field, array $params = []) use (&$errors) {
            $errors[] = ['code' => $code, 'field' => $field, 'params' => $params];
        };

        $text = function (string $field, int $max, bool $multiline = false) use ($raw, $error): ?string {
            $value = $multiline
                ? trim(preg_replace("/\r\n?/", "\n", $raw[$field]))
                : trim(preg_replace('/\s+/u', ' ', $raw[$field]));

            if ($value === '') {
                if (self::FIELDS[$field]['required'] ?? false) {
                    $error('required', $field);
                }

                return null;
            }

            if (mb_strlen($value) > $max) {
                $error('too_long', $field, ['max' => $max]);
            }

            return $value;
        };

        $choice = function (string $field, array $options) use ($raw, $error): ?string {
            $value = trim($raw[$field]);

            if ($value === '') {
                if (self::FIELDS[$field]['required'] ?? false) {
                    $error('required', $field);
                }

                return null;
            }

            $key = $this->normalize($value);

            foreach ($options as $option => $spellings) {
                foreach ($spellings as $spelling) {
                    if ($this->normalize($spelling) === $key) {
                        return $option;
                    }
                }
            }

            $error('allowed', $field, ['value' => mb_substr($value, 0, 50), 'allowed' => implode(', ', array_keys($options))]);

            return null;
        };

        $date = function (string $field) use ($raw, $error): ?string {
            $value = trim($raw[$field]);

            if ($value === '') {
                return null;
            }

            $parsed = $this->parseDate($value);

            if ($parsed === null) {
                $error('date', $field);
            } elseif ($parsed > now(config('ai.timezone'))->toDateString()) {
                $error('date_future', $field);
            }

            return $parsed;
        };

        $data = [
            'name' => $text('name', self::MAX_TEXT),
            'direction' => $choice('direction', self::DIRECTIONS),
            'breed' => $text('breed', self::MAX_TEXT),
            'birth_date' => $date('birth_date'),
            'initial_weight' => null,
            'gender' => $choice('gender', self::GENDERS),
            'notes' => $text('notes', self::MAX_NOTES, true),
            'has_passport' => false,
            'passport_number' => $text('passport_number', self::MAX_TEXT),
            'chip_number' => $text('chip_number', self::MAX_TEXT),
            'passport_issue_date' => $date('passport_issue_date'),
        ];

        $weight = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim($raw['initial_weight']));

        if ($weight !== '') {
            if (!preg_match('/^\d+(\.\d+)?$/', $weight)) {
                $error('number', 'initial_weight');
            } elseif ((float) $weight <= 0 || (float) $weight > self::MAX_WEIGHT) {
                $error('number_range', 'initial_weight', ['max' => self::MAX_WEIGHT]);
            } else {
                $data['initial_weight'] = round((float) $weight, 2);
            }
        }

        $data['current_weight'] = $data['initial_weight'];

        // Excel uzun raqamni "9,82E+14" ko'rinishiga qisqartiradi — asl xonalar yo'qolgan
        foreach (['passport_number', 'chip_number'] as $field) {
            if ($data[$field] !== null && preg_match('/^[+-]?\d+([.,]\d+)?\s*E[+-]\d+$/i', $data[$field])) {
                $error('scientific', $field);
            }
        }

        // Bo'sh qoldirilsa: pasport raqami yozilgan bo'lsa — "bor" deb hisoblanadi
        $hasPassport = $this->normalize($raw['has_passport']);

        if ($hasPassport === '') {
            $data['has_passport'] = $data['passport_number'] !== null;
        } elseif (in_array($hasPassport, array_map(fn ($v) => $this->normalize($v), self::BOOLEANS), true)) {
            $data['has_passport'] = true;
        } elseif (!in_array($hasPassport, array_map(fn ($v) => $this->normalize($v), self::BOOLEANS_FALSE), true)) {
            $error('allowed', 'has_passport', ['value' => mb_substr(trim($raw['has_passport']), 0, 50), 'allowed' => "ha, yo'q"]);
        }

        return [$data, $errors];
    }

    /**
     * dd.mm.yyyy, yyyy-mm-dd yoki Excel sana raqami => Y-m-d. Noto'g'ri bo'lsa null.
     */
    private function parseDate(string $value): ?string
    {
        // Excel ba'zan sanaga vaqt qo'shib yozadi: "15.03.2023 0:00"
        $value = preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?$/', '', $value);

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^\d{5}(\.\d+)?$/', $value)) {
            // Excel sana raqami: 5 xonali (1927-2173 yillar), shunda "2023" kabi yil sana deb olinmaydi
            $converted = SpreadsheetReader::excelDate((float) $value);

            if ($converted === null) {
                return null;
            }

            [$year, $month, $day] = array_map('intval', explode('-', $converted));
        } else {
            return null;
        }

        if ($year < 1900 || !checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Sarlavha ustun indeksi => maydon. Noma'lum ustunlar (masalan "Xato sababi") e'tiborga olinmaydi.
     */
    private function mapColumns(array $header): array
    {
        $lookup = [];

        foreach (self::FIELDS as $field => $definition) {
            foreach ([$definition['label'], ...$definition['aliases']] as $name) {
                $lookup[$this->normalizeHeader($name)] = $field;
            }
        }

        $columns = [];

        foreach ($header as $index => $title) {
            $field = $lookup[$this->normalizeHeader($title)] ?? null;

            if ($field !== null && !in_array($field, $columns, true)) {
                $columns[$index] = $field;
            }
        }

        return $columns;
    }

    private function normalizeHeader(string $title): string
    {
        return $this->normalize(preg_replace('/\([^)]*\)|\*/u', '', $title));
    }

    /**
     * Kichik harf, faqat harf va raqamlar: "Go'sht" = "gosht", "Tug'ilgan sana" = "tugilgansana".
     */
    private function normalize(string $value): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim($value)));
    }

    /**
     * MySQL'dagi unique indeks katta-kichik harfni farqlamaydi, shuning uchun solishtirish ham shunday.
     */
    private function passportKey(string $number): string
    {
        return mb_strtolower(trim($number));
    }

    /**
     * Xato qatorlar CSV'sida formulaga o'xshash qiymatlar oldiga ' qo'yiladi; qayta yuklanganda olib tashlanadi.
     */
    private function unescapeFormula(string $value): string
    {
        return preg_match("/^'[=+\-@]/", $value) ? substr($value, 1) : $value;
    }

    private function csv(array $rows, array $extraHeaders = []): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [...array_values($this->fieldLabels()), ...$extraHeaders], ';', '"', '', "\r\n");

        foreach ($rows as $row) {
            $row = array_map(fn ($value) => preg_match('/^[=+\-@]/', (string) $value) ? "'" . $value : $value, $row);
            fputcsv($handle, $row, ';', '"', '', "\r\n");
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF" . $content;
    }
}
