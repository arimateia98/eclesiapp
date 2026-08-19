<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Policies\ParishPolicy;
use App\Modules\PastoralOrganization\Models\Servant;
use App\Modules\PastoralOrganization\Policies\ServantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Parish::class, ParishPolicy::class);
        Gate::policy(Servant::class, ServantPolicy::class);
    }
}
