<?php

namespace App\Providers;

use App\Models\Insiden;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Rekonsiliasi;
use App\Policies\InsidenPolicy;
use App\Policies\PemasukanPolicy;
use App\Policies\PengeluaranPolicy;
use App\Policies\RekonsiliasiPolicy;
use App\Services\BrandingService;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(Pemasukan::class, PemasukanPolicy::class);
        Gate::policy(Pengeluaran::class, PengeluaranPolicy::class);
        Gate::policy(Rekonsiliasi::class, RekonsiliasiPolicy::class);
        Gate::policy(Insiden::class, InsidenPolicy::class);

        // Identitas aplikasi (logo, nama, brand, title) tersedia di semua view,
        // termasuk layout login yang dirender tanpa user login.
        View::composer('*', function ($view) {
            $view->with('branding', $this->app->make(BrandingService::class)->semua());
        });

        $flush = fn () => $this->app->make(DashboardService::class)->flush();

        foreach ([Pemasukan::class, Pengeluaran::class, Rekonsiliasi::class, Insiden::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }
}
