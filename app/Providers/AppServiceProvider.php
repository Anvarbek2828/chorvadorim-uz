<?php

namespace App\Providers;

use App\Models\User;
use App\Services\CurrentFarm;
use App\Support\FarmPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Joriy ferma har bir so'rov uchun bir marta aniqlanadi
        $this->app->scoped(CurrentFarm::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ferma huquqlari: Gate::allows('animals.delete') / ->can('animals.delete') => $user->canInFarm(...)
        foreach (FarmPermissions::all() as $permission) {
            Gate::define($permission, fn (User $user) => $user->canInFarm($permission));
        }
    }
}
