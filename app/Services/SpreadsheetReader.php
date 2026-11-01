<?php

namespace App\Services;

use DateTimeImmutable;
use Generator;
use SimpleXMLElement;
use ZipArchive;

/**
 * .xlsx (faqat birinchi varaq) va .csv fayllarni qo'shimcha paketlarsiz o'qiydi.
 * Natija: [fayldagi qator raqami => [ustun indeksi => matn]]; bo'sh qatorlar tashlab yuboriladi.
 */
class SpreadsheetReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_PKG_REL = 'http://schemas.openxmlformats.org/package/2006/relationships';

    // Zip ichidagi bitta XML qismining ochilgandagi maksimal hajmi (zip-bomb'dan himoya)
    private const MAX_XML_BYTES = 40 * 1024 * 1024;

    // Bundan o'ngdagi ustunlar o'qilmaydi
    private const MAX_COLUMNS = 50;

    // Excel'ning o'rnatilgan sana formatlari (numFmtId)
    private const BUILTIN_DATE_FORMATS = [14, 15, 16, 17, 18, 19, 20, 21, 22, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 45, 46, 47, 50, 51, 52, 53, 54, 55, 56, 57, 58];

    public static function xlsxSupported(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * Excel'dagi sana raqamini (1900 tizimi, 1904 ixtiyoriy) Y-m-d ko'rinishiga o'tkazadi.
     */
    public static function excelDate(float $serial, bool $date1904 = false): ?string
    {
        $days = (int) floor($serial);

        if ($days < 1 || $days > 2958465) {
            return null;
        }

        return (new DateTimeImmutable($date1904 ? '1904-01-01' : '1899-12-30'))
            ->modify("+{$days} days")
            ->format('Y-m-d');
    }

    /**
     * @param  int  $maxRows  sarlavhadan tashqari bo'sh bo'lmagan qatorlar chegarasi
     * @return array<int, array<int, string>>
     */
    public function read(string $path, string $extension, int $maxRows): array
    {
        $source = match ($extension) {
            'xlsx' => $this->xlsxRows($path),
            'csv' => $this->csvRows($path),
            default => throw new SpreadsheetReaderException(__("Fayl turi noto'g'ri. Faqat .xlsx yoki .csv fayl yuklang.")),
        };

        $rows = [];

        foreach ($source as $number => $cells) {
            $cells = array_map(fn ($value) => trim(str_replace("\u{00A0}", ' ', (string) $value)), $cells);

            if (implode('', $cells) === '') {
                continue;
            }

            // Birinchi qator — sarlavha, shuning uchun chegara $maxRows + 1
            if (count($rows) > $maxRows) {
                throw new SpreadsheetReaderException(__("Faylda :max tadan ortiq qator bor. Iltimos, faylni bir necha qismga bo'lib yuklang.", ['max' => $maxRows]));
            }

            $rows[$number] = $cells;
        }

        return $rows;
    }

    private function csvRows(string $path): Generator
    {
        $content = (string) file_get_contents($path);

        // .xlsx yoki eski .xls faylning nomi .csv qilib o'zgartirilgan bo'lsa
        if (str_starts_with($content, "PK\x03\x04") || str_starts_with($content, "\xD0\xCF\x11\xE0")) {
            throw new SpreadsheetReaderException(__("Fayl mazmuni CSV formatiga mos emas (bu Excel fayliga o'xshaydi). Uni .xlsx sifatida yoki Excel'da \"CSV\" formatida saqlab yuklang."));
        }

        $content = $this->toUtf8($content);

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content)) {
            throw new SpreadsheetReaderException(__("Fayl mazmuni CSV formatiga mos emas. Faqat .xlsx yoki .csv fayl yuklang."));
        }

        $delimiter = $this->detectDelimiter($content);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        try {
            $number = 0;

            // Bo'sh escape belgisi: qo'shtirnoq ichidagi "" — RFC 4180 bo'yicha
            while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $number++;

                if ($cells === [null]) {
                    continue;
                }

                yield $number => array_slice($cells, 0, self::MAX_COLUMNS);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * UTF-8 (BOM bilan yoki BOMsiz), UTF-16 (BOM bilan) va Windows-1251 ni aniqlab, UTF-8 ga o'tkazadi.
     */
    private function toUtf8(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        } elseif (str_starts_with($content, "\xFF\xFE")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        }

        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        return mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
    }

    /**
     * Birinchi bo'sh bo'lmagan qatorda (qo'shtirnoqlardan tashqarida) qaysi belgi ko'p bo'lsa — o'sha ajratgich.
     */
    private function detectDelimiter(string $content): string
    {
        $line = '';

        foreach (preg_split('/\r\n|\r|\n/', $content) as $candidate) {
            if (trim($candidate) !== '') {
                $line = $candidate;
                break;
            }
        }

        $line = preg_replace('/"[^"]*"/', '', $line);
        $best = ';';
        $bestCount = 0;

        foreach ([';', ',', "\t"] as $delimiter) {
            $count = substr_count($line, $delimiter);

            if ($count > $bestCount) {
                $best = $delimiter;
                $bestCount = $count;
            }
        }

        return $best;
    }

    private function xlsxRows(string $path): Generator
    {
        if (!self::xlsxSupported()) {
            throw new SpreadsheetReaderException(__("Serverda .xlsx fayllarni o'qib bo'lmaydi. Iltimos, faylni Excel'da \"CSV\" formatida saqlab yuklang."));
        }

        $handle = fopen($path, 'rb');
        $signature = $handle ? fread($handle, 4) : '';
        $handle && fclose($handle);

        if ($signature !== "PK\x03\x04") {
            throw new SpreadsheetReaderException(__("Fayl mazmuni .xlsx formatiga mos emas. Faylni Excel'da qayta saqlab yuklang."));
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new SpreadsheetReaderException(__("Faylni o'qib bo'lmadi. U buzilgan bo'lishi mumkin."));
        }

        try {
            $workbook = $this->xml($zip, 'xl/workbook.xml');

            if ($workbook === null) {
                throw new SpreadsheetReaderException(__("Fayl mazmuni .xlsx formatiga mos emas. Faylni Excel'da qayta saqlab yuklang."));
            }

            $workbookMain = $workbook->children(self::NS_MAIN);
            $date1904 = isset($workbookMain->workbookPr)
                && in_array(strtolower((string) $workbookMain->workbookPr->attributes()['date1904']), ['1', 'true'], true);

            $sheet = $this->xml($zip, $this->firstSheetPath($zip, $workbook));
            $sharedStrings = $this->sharedStrings($zip);
            $dateStyles = $this->dateStyles($zip);
        } finally {
            $zip->close();
        }

        if ($sheet === null) {
            throw new SpreadsheetReaderException(__("Faylni o'qib bo'lmadi. U buzilgan bo'lishi mumkin."));
        }

        $sheetMain = $sheet->children(self::NS_MAIN);

        if (!isset($sheetMain->sheetData)) {
            return;
        }

        $number = 0;

        foreach ($sheetMain->sheetData->children(self::NS_MAIN)->row as $row) {
            $number = (int) $row->attributes()['r'] ?: $number + 1;
            $cells = [];
            $column = -1;

            foreach ($row->children(self::NS_MAIN)->c as $cell) {
                $reference = (string) $cell->attributes()['r'];
                $column = $reference !== '' ? $this->columnIndex($reference) : $column + 1;

                if ($column < self::MAX_COLUMNS) {
                    $cells[$column] = $this->cellValue($cell, $sharedStrings, $dateStyles, $date1904);
                }
            }

            if ($cells === []) {
                continue;
            }

            ksort($cells);
            $list = [];

            for ($i = 0, $last = array_key_last($cells); $i <= $last; $i++) {
                $list[] = $cells[$i] ?? '';
            }

            yield $number => $list;
        }
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings, array $dateStyles, bool $date1904): string
    {
        $attributes = $cell->attributes();
        $children = $cell->children(self::NS_MAIN);
        $value = (string) $children->v;

        return match ((string) $attributes['t']) {
            's' => $sharedStrings[(int) $value] ?? '',
            'inlineStr' => isset($children->is) ? $this->richText($children->is) : '',
            'str' => $value,
            'b' => $value === '1' ? 'TRUE' : 'FALSE',
            'e' => '',
            'd' => substr($value, 0, 10),
            default => $this->numberValue($value, $dateStyles[(int) $attributes['s']] ?? false, $date1904),
        };
    }

    private function numberValue(string $value, bool $isDate, bool $date1904): string
    {
        if ($value === '' || !is_numeric($value)) {
            return $value;
        }

        $number = (float) $value;

        if ($isDate) {
            return self::excelDate($number, $date1904) ?? $value;
        }

        // 450.30000000000001 kabi ko'rinishlar va katta butun sonlarning E-yozuvi oldini olish
        if (floor($number) === $number && abs($number) < 1e15) {
            return sprintf('%.0f', $number);
        }

        return (string) $number;
    }

    /**
     * Oddiy (<t>) va formatlangan (<r><t>) matn; fonetik yozuv (<rPh>) e'tiborga olinmaydi.
     */
    private function richText(SimpleXMLElement $node): string
    {
        $children = $node->children(self::NS_MAIN);
        $text = (string) $children->t;

        foreach ($children->r as $run) {
            $text .= (string) $run->children(self::NS_MAIN)->t;
        }

        return $text;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $this->xml($zip, 'xl/sharedStrings.xml');
        $strings = [];

        foreach ($xml?->children(self::NS_MAIN)->si ?? [] as $item) {
            $strings[] = $this->richText($item);
        }

        return $strings;
    }

    /**
     * cellXfs indeksi => shu uslub sana formatimi.
     */
    private function dateStyles(ZipArchive $zip): array
    {
        $xml = $this->xml($zip, 'xl/styles.xml');

        if ($xml === null) {
            return [];
        }

        $main = $xml->children(self::NS_MAIN);
        $customFormats = [];

        foreach (isset($main->numFmts) ? $main->numFmts->children(self::NS_MAIN)->numFmt : [] as $format) {
            $attributes = $format->attributes();
            $customFormats[(int) $attributes['numFmtId']] = (string) $attributes['formatCode'];
        }

        $styles = [];

        foreach (isset($main->cellXfs) ? $main->cellXfs->children(self::NS_MAIN)->xf : [] as $xf) {
            $formatId = (int) $xf->attributes()['numFmtId'];
            $styles[] = in_array($formatId, self::BUILTIN_DATE_FORMATS, true)
                || (isset($customFormats[$formatId]) && $this->isDateFormat($customFormats[$formatId]));
        }

        return $styles;
    }

    private function isDateFormat(string $code): bool
    {
        // Qo'shtirnoqdagi matn, [rang]/[$-419] bloklari va \x, _x, *x belgilari olib tashlanadi
        $code = preg_replace(['/"[^"]*"/', '/\[[^\]]*\]/', '/\\\\./', '/[_*]./'], '', $code);

        return (bool) preg_match('/[dy]/i', $code);
    }

    private function firstSheetPath(ZipArchive $zip, SimpleXMLElement $workbook): string
    {
        $main = $workbook->children(self::NS_MAIN);
        $sheet = isset($main->sheets) ? $main->sheets->children(self::NS_MAIN)->sheet[0] : null;
        $relationId = $sheet !== null ? (string) $sheet->attributes(self::NS_REL)['id'] : '';
        $relations = $relationId !== '' ? $this->xml($zip, 'xl/_rels/workbook.xml.rels') : null;

        foreach ($relations?->children(self::NS_PKG_REL)->Relationship ?? [] as $relation) {
            $attributes = $relation->attributes();

            if ((string) $attributes['Id'] === $relationId) {
                $target = (string) $attributes['Target'];

                return $this->normalizePath(str_starts_with($target, '/') ? $target : 'xl/' . $target);
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function normalizePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '..') {
                array_pop($parts);
            } elseif ($part !== '' && $part !== '.') {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }

    private function columnIndex(string $reference): int
    {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);

            if ($index > self::MAX_COLUMNS) {
                return self::MAX_COLUMNS;
            }
        }

        return $index - 1;
    }

    private function xml(ZipArchive $zip, string $name): ?SimpleXMLElement
    {
        $stat = $zip->statName($name);

        if ($stat === false) {
            return null;
        }

        if ($stat['size'] > self::MAX_XML_BYTES) {
            throw new SpreadsheetReaderException(__("Faylni o'qib bo'lmadi. U buzilgan bo'lishi mumkin."));
        }

        $content = $zip->getFromName($name);

        // .xlsx ichida DOCTYPE bo'lmaydi; bo'lsa — XML entity hujumlarining oldini olish uchun rad etiladi
        if ($content === false || stripos($content, '<!DOCTYPE') !== false) {
            throw new SpreadsheetReaderException(__("Faylni o'qib bo'lmadi. U buzilgan bo'lishi mumkin."));
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new SpreadsheetReaderException(__("Faylni o'qib bo'lmadi. U buzilgan bo'lishi mumkin."));
        }

        return $xml;
    }
}
