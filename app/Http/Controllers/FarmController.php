<?php

namespace App\Http\Controllers;

use App\Models\FarmAuditLog;
use App\Models\FarmUser;
use App\Services\CurrentFarm;
use App\Services\FarmAuditService;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Joriy fermani almashtirish: faqat foydalanuvchining o'z faol a'zoligiga.
     */
    public function switch(Request $request, CurrentFarm $currentFarm)
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $membership = FarmUser::where('user_id', $user->id)
            ->where('farm_id', $validated['farm_id'])
            ->where('status', FarmUser::STATUS_ACTIVE)
            ->first();

        if (!$membership) {
            abort(404);
        }

        $user->forceFill(['current_farm_id' => $membership->farm_id])->save();
        $currentFarm->forget($user);

        return redirect()->route('dashboard');
    }

    /**
     * Ferma sozlamalari (huquq: farm.settings).
     */
    public function edit()
    {
        return view('farm.settings', ['farm' => $this->currentFarm()]);
    }

    public function update(Request $request, FarmAuditService $audit)
    {
        $farm = $this->currentFarm();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $oldName = $farm->name;
        $farm->name = trim($validated['name']);
        $farm->save();

        if ($oldName !== $farm->name) {
            $audit->log($farm, FarmAuditLog::ACTION_FARM_RENAMED, 'farm', $farm->id, ['nomi' => $oldName], ['nomi' => $farm->name]);
        }

        return redirect()->route('farm-settings.edit')->with('success', __('Ferma nomi saqlandi.'));
    }
}
