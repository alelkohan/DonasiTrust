<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Policies\CampaignPolicy;
use App\Services\MidtransPaymentGateway;
use App\Services\MockPaymentGateway;
use App\Services\PaymentGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            return match (config('donasi.gateway')) {
                'midtrans' => new MidtransPaymentGateway,
                default => new MockPaymentGateway,
            };
        });
    }

    public function boot(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);

        // Otomatis sesuaikan Root URL & Scheme dengan host yang sedang mengakses (support IP lokal & Tunnel HP)
        if (! $this->app->runningInConsole() && request()->getHost()) {
            URL::forceRootUrl(request()->schemeAndHttpHost());
            if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
                URL::forceScheme('https');
            }
        } elseif ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Pastikan menu & badge notifikasi selalu tampil konsisten di semua halaman dasbor
        \Illuminate\Support\Facades\View::composer('layouts.dashboard', function ($view) {
            $user = auth()->user();
            if (! $user) return;

            if ($user->isAdmin()) {
                $menu = [
                    [
                        'label' => 'Ringkasan',
                        'url' => route('admin.dashboard'),
                        'active' => request()->routeIs('admin.dashboard'),
                    ],
                    [
                        'label' => 'Review kampanye',
                        'url' => route('admin.kampanye.index'),
                        'active' => request()->routeIs('admin.kampanye.*'),
                        'badge' => \App\Models\Campaign::where('status', \App\Models\Campaign::STATUS_PENDING)->count() ?: null,
                    ],
                    [
                        'label' => 'Pencairan dana',
                        'url' => route('admin.pencairan.index'),
                        'active' => request()->routeIs('admin.pencairan.*'),
                        'badge' => \App\Models\Disbursement::where('status', \App\Models\Disbursement::STATUS_PENDING)->count() ?: null,
                    ],
                    [
                        'label' => 'Verifikasi LPJ',
                        'url' => route('admin.lpj.index'),
                        'active' => request()->routeIs('admin.lpj.*'),
                        'badge' => \App\Models\ExpenseReport::where('status', \App\Models\ExpenseReport::STATUS_PENDING)->count() ?: null,
                    ],
                    [
                        'label' => 'Verifikasi pengguna',
                        'url' => route('admin.pengguna.index'),
                        'active' => request()->routeIs('admin.pengguna.*'),
                        'badge' => \App\Models\User::where('verification_status', \App\Models\User::VERIFICATION_PENDING)->count() ?: null,
                    ],
                    [
                        'label' => 'Jejak audit',
                        'url' => route('admin.audit.index'),
                        'active' => request()->routeIs('admin.audit.*'),
                    ],
                ];
            } elseif ($user->isPengaju()) {
                $menu = [
                    [
                        'label' => 'Ringkasan',
                        'url' => route('pengaju.dashboard'),
                        'active' => request()->routeIs('pengaju.dashboard'),
                    ],
                    [
                        'label' => 'Kampanye saya',
                        'url' => route('pengaju.kampanye.index'),
                        'active' => request()->routeIs('pengaju.kampanye.*'),
                    ],
                    [
                        'label' => 'Profil saya',
                        'url' => route('profil.edit'),
                        'active' => request()->routeIs('profil.edit'),
                    ],
                    [
                        'label' => 'Verifikasi identitas',
                        'url' => route('verifikasi.identitas'),
                        'active' => request()->routeIs('verifikasi.identitas'),
                    ],
                ];
            } else {
                $menu = [
                    [
                        'label' => 'Dasbor',
                        'url' => route('donatur.dashboard'),
                        'active' => request()->routeIs('donatur.dashboard'),
                    ],
                    [
                        'label' => 'Profil saya',
                        'url' => route('profil.edit'),
                        'active' => request()->routeIs('profil.edit'),
                    ],
                    [
                        'label' => 'Verifikasi identitas',
                        'url' => route('verifikasi.identitas'),
                        'active' => request()->routeIs('verifikasi.identitas'),
                    ],
                ];
            }

            $view->with('menu', $menu);
        });
    }
}
