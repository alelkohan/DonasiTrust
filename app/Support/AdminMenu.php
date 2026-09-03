<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\ExpenseReport;
use App\Models\User;

/**
 * Satu-satunya definisi menu samping admin.
 *
 * Dibuat terpusat setelah tiga halaman admin sempat kehilangan menunya karena
 * tiap view menyalin array-nya sendiri-sendiri. Sekarang menambah satu halaman
 * cukup diubah di sini.
 */
class AdminMenu
{
    /**
     * @param  string  $active  kunci halaman yang sedang dibuka
     * @param  bool  $withBadges  hitung antrean yang menunggu (satu query per item)
     * @return array<int, array{label: string, url: string, active: bool, badge: int|null}>
     */
    public static function items(string $active = '', bool $withBadges = true): array
    {
        $antrean = $withBadges ? self::antrean() : [];

        $definisi = [
            'ringkasan' => ['Ringkasan', 'admin.dashboard', null],
            'kampanye' => ['Review kampanye', 'admin.kampanye.index', 'kampanye'],
            'pencairan' => ['Pencairan dana', 'admin.pencairan.index', 'pencairan'],
            'lpj' => ['Verifikasi LPJ', 'admin.lpj.index', 'lpj'],
            'pengguna' => ['Verifikasi pengguna', 'admin.pengguna.index', 'pengguna'],
            'audit' => ['Jejak audit', 'admin.audit.index', null],
        ];

        $menu = [];

        foreach ($definisi as $kunci => [$label, $route, $badgeKey]) {
            $jumlah = $badgeKey ? ($antrean[$badgeKey] ?? 0) : 0;

            $menu[] = [
                'label' => $label,
                'url' => route($route),
                'active' => $kunci === $active,
                'badge' => $jumlah > 0 ? $jumlah : null,
            ];
        }

        return $menu;
    }

    /** @return array<string, int> */
    private static function antrean(): array
    {
        return [
            'kampanye' => Campaign::where('status', Campaign::STATUS_PENDING)->count(),
            'pencairan' => Disbursement::where('status', Disbursement::STATUS_PENDING)->count(),
            'lpj' => ExpenseReport::where('status', ExpenseReport::STATUS_PENDING)->count(),
            'pengguna' => User::where('verification_status', User::VERIFICATION_PENDING)->count(),
        ];
    }
}
