<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmAuditLog;
use DateTimeInterface;
use Throwable;

class FarmAuditService
{
    /**
     * Ferma amalini jurnalga yozadi. Amalni bajargan — kirgan foydalanuvchi.
     * Jurnalga yozib bo'lmasa ham asosiy amal bekor bo'lmasligi uchun xato faqat jurnalga (report) yoziladi.
     */
    public function log(Farm|int $farm, string $action, string $objectType, ?int $objectId, array $old = [], array $new = []): ?FarmAuditLog
    {
        return $this->write($farm, auth()->id(), $action, $objectType, $objectId, $old, $new);
    }

    /**
     * Kirgan foydalanuvchisiz kontekstdagi amal (masalan, Telegram bot tugmasi): bajaruvchi aniq beriladi.
     */
    public function logAs(int $userId, Farm|int $farm, string $action, string $objectType, ?int $objectId, array $old = [], array $new = []): ?FarmAuditLog
    {
        return $this->write($farm, $userId, $action, $objectType, $objectId, $old, $new);
    }

    /**
     * Tizim amali (masalan, tarif pasayganda xodimni to'xtatish): user_id bo'sh qoladi.
     */
    public function logSystem(Farm|int $farm, string $action, string $objectType, ?int $objectId, array $old = [], array $new = []): ?FarmAuditLog
    {
        return $this->write($farm, null, $action, $objectType, $objectId, $old, $new);
    }

    /**
     * Ikki holat orasida faqat o'zgargan maydonlar: [eski, yangi]. Hech narsa o'zgarmagan bo'lsa — ikkalasi bo'sh.
     *
     * @return array{0: array, 1: array}
     */
    public function diff(array $old, array $new): array
    {
        $old = array_map(fn ($value) => $this->normalize($value), $old);
        $new = array_map(fn ($value) => $this->normalize($value), $new);

        $changed = array_keys(array_filter(
            $new,
            fn ($value, $key) => !array_key_exists($key, $old) || $old[$key] !== $value,
            ARRAY_FILTER_USE_BOTH
        ));

        return [
            array_intersect_key($old, array_flip($changed)),
            array_intersect_key($new, array_flip($changed)),
        ];
    }

    private function write(Farm|int $farm, ?int $userId, string $action, string $objectType, ?int $objectId, array $old, array $new): ?FarmAuditLog
    {
        try {
            return FarmAuditLog::create([
                'farm_id' => $farm instanceof Farm ? $farm->id : $farm,
                'user_id' => $userId,
                'amal' => $action,
                'obyekt_turi' => $objectType,
                'obyekt_id' => $objectId,
                'eski_qiymat' => $old === [] ? null : array_map(fn ($value) => $this->normalize($value), $old),
                'yangi_qiymat' => $new === [] ? null : array_map(fn ($value) => $this->normalize($value), $new),
                'ip' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Bazadan kelgan sonlar (450.0, "450.00") va formadagi ("450") bir xil ko'rinishda solishtiriladi
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (is_string($value) && is_numeric($value) && str_contains($value, '.')) {
            return rtrim(rtrim($value, '0'), '.');
        }

        return $value;
    }
}
