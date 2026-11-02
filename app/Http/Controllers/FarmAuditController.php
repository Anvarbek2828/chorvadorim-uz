<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\FarmAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Ferma jurnali (huquq: audit.view): faqat joriy fermaning yozuvlari.
 */
class FarmAuditController extends Controller
{
    public function index(Request $request)
    {
        $farm = $this->currentFarm();

        $userIds = FarmAuditLog::where('farm_id', $farm->id)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);

        $filters = [
            'user' => $users->contains('id', (int) $request->query('user')) ? (int) $request->query('user') : null,
            'amal' => in_array($request->query('amal'), FarmAuditLog::ACTIONS, true) ? $request->query('amal') : '',
        ];

        $logs = FarmAuditLog::with('user')
            ->where('farm_id', $farm->id)
            ->when($filters['user'] !== null, fn ($q) => $q->where('user_id', $filters['user']))
            ->when($filters['amal'] !== '', fn ($q) => $q->where('amal', $filters['amal']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        // Hayvon nomlari bitta so'rov bilan (arxivdagilari ham; faqat shu fermaniki)
        $animalIds = $logs->getCollection()->where('obyekt_turi', 'animal')->pluck('obyekt_id')->filter()->unique();

        return view('farm.audit-log', [
            'logs' => $logs,
            'users' => $users,
            'filters' => $filters,
            'animalNames' => Animal::withTrashed()->where('farm_id', $farm->id)->whereIn('id', $animalIds)->pluck('name', 'id'),
        ]);
    }
}
