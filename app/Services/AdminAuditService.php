<?php

namespace App\Services;

use App\Models\AdminAuditLog;

class AdminAuditService
{
    /**
     * Admin amalini jurnalga yozadi. Faqat haqiqatan o'zgargan maydonlar saqlanadi;
     * hech narsa o'zgarmagan bo'lsa, yozuv qo'shilmaydi. $farmId — amal tegishli ferma (platforma amallarida null).
     */
    public function log(string $action, string $objectType, ?int $objectId, array $old, array $new, ?int $adminId = null, ?string $ip = null, ?int $farmId = null): ?AdminAuditLog
    {
        $changedKeys = array_keys(array_filter(
            $new,
            fn ($value, $key) => !array_key_exists($key, $old) || $old[$key] !== $value,
            ARRAY_FILTER_USE_BOTH
        ));

        if ($changedKeys === []) {
            return null;
        }

        return AdminAuditLog::create([
            'farm_id' => $farmId,
            'admin_id' => $adminId ?? auth()->id(),
            'amal' => $action,
            'obyekt_turi' => $objectType,
            'obyekt_id' => $objectId,
            'eski_qiymat' => array_intersect_key($old, array_flip($changedKeys)),
            'yangi_qiymat' => array_intersect_key($new, array_flip($changedKeys)),
            'ip' => $ip ?? request()?->ip(),
        ]);
    }
}
