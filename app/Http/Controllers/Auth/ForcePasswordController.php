<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FarmUser;
use App\Services\CurrentFarm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Egasi bergan vaqtinchalik parolni birinchi kirishda almashtirish (EnsurePasswordChanged).
 */
class ForcePasswordController extends Controller
{
    public function edit(Request $request, CurrentFarm $currentFarm)
    {
        if (!$currentFarm->membership($request->user())?->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-password');
    }

    public function update(Request $request, CurrentFarm $currentFarm)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'password.different' => __('Yangi parol vaqtinchalik paroldan farq qilishi kerak.'),
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Parol akkauntga tegishli: barcha fermalardagi belgi tushiriladi
        FarmUser::where('user_id', $user->id)->update(['must_change_password' => false]);
        $currentFarm->forget($user);

        return redirect()->route('dashboard')->with('status', 'password-updated');
    }
}
