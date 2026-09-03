<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_rantai_utuh_setelah_beberapa_entri(): void
    {
        $audit = app(AuditLogger::class);
        $user = User::factory()->create();

        $audit->record('user.registered', $user, ['a' => 1], $user);
        $audit->record('user.verified', $user, ['b' => 2], $user);
        $audit->record('campaign.created', $user, ['c' => 3], $user);

        $hasil = $audit->verifyChain();

        $this->assertTrue($hasil['valid'], $hasil['reason'] ?? '');
        $this->assertSame(3, $hasil['checked']);
    }

    public function test_mengubah_entri_lama_memutus_rantai(): void
    {
        $audit = app(AuditLogger::class);
        $user = User::factory()->create();

        $audit->record('user.registered', $user, ['a' => 1], $user);
        $target = $audit->record('user.verified', $user, ['b' => 2], $user);
        $audit->record('campaign.created', $user, ['c' => 3], $user);

        // Manipulasi diam-diam: ubah isi entri tanpa menyentuh hash-nya.
        AuditLog::whereKey($target->id)->update(['action' => 'user.rejected']);

        $hasil = $audit->verifyChain();

        $this->assertFalse($hasil['valid']);
        $this->assertSame($target->id, $hasil['broken_at']);
    }
}
