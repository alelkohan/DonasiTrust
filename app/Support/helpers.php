<?php

use App\Support\Rupiah;

if (! function_exists('rupiah')) {
    /** Format nominal rupiah penuh: 1500000 -> "Rp1.500.000". */
    function rupiah(int|float|null $amount): string
    {
        return Rupiah::format($amount);
    }
}

if (! function_exists('rupiah_ringkas')) {
    /** Format ringkas untuk kartu & grafik: 1500000 -> "Rp1,5 jt". */
    function rupiah_ringkas(int|float|null $amount): string
    {
        return Rupiah::compact($amount);
    }
}
