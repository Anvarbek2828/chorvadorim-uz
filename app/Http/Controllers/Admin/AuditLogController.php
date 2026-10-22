<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Plan;
use App\Models\User;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AdminAuditLog::with('admin')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50);

        // Obyekt nomlarini bitta so'rov bilan olish
        $ids = fn (string $type) => $logs->getCollection()->where('obyekt_turi', $type)->pluck('obyekt_id')->filter()->unique();

        return view('admin.audit-log', [
            'logs' => $logs,
            'userNames' => User::whereIn('id', $ids('user'))->pluck('name', 'id'),
            'planNames' => Plan::whereIn('id', $ids('plan'))->pluck('nomi', 'id'),
        ]);
    }
}
