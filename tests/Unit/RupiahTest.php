<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_format_penuh(): void
    {
        $this->assertSame('Rp1.500.000', Rupiah::format(1_500_000));
        $this->assertSame('Rp0', Rupiah::format(0));
        $this->assertSame('Rp0', Rupiah::format(null));
    }

    public function test_format_ringkas(): void
    {
        $this->assertSame('Rp1,5 jt', Rupiah::compact(1_500_000));
        $this->assertSame('Rp2 jt', Rupiah::compact(2_000_000));
        $this->assertSame('Rp50 rb', Rupiah::compact(50_000));
        $this->assertSame('Rp1,2 M', Rupiah::compact(1_200_000_000));
        $this->assertSame('Rp900', Rupiah::compact(900));
    }

    public function test_parse_membuang_karakter_bukan_angka(): void
    {
        $this->assertSame(1_500_000, Rupiah::parse('Rp 1.500.000'));
        $this->assertSame(0, Rupiah::parse(''));
        $this->assertSame(250000, Rupiah::parse('250000'));
    }
}
