<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_DONATUR = 'donatur';
    public const ROLE_PENGAJU = 'pengaju';
    public const ROLE_ADMIN = 'admin';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'organization',
        'identity_document_path',
        'identity_number_last4',
        'identity_number_hash',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'verification_status',
        'verification_note',
        'verified_at',
        'verified_by',
        // Catatan: kolom totp_* sengaja TIDAK ada di daftar ini. Kunci dua
        // langkah tidak boleh bisa ikut terisi lewat mass assignment dari
        // request; hanya TwoFactorController yang menulisnya secara eksplisit.
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Kunci TOTP setara kata sandi: siapa pun yang membacanya bisa
        // membangkitkan kode yang sah kapan saja.
        'totp_secret',
        'totp_recovery_codes',
        'identity_document_path',
        'identity_number_last4',
        'identity_number_hash',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'password' => 'hashed',
            // Terenkripsi dengan APP_KEY, bukan di-hash: kunci TOTP harus bisa
            // dibaca kembali untuk menghitung kode, jadi hash searah tidak bisa
            // dipakai di sini seperti pada kata sandi.
            'totp_secret' => 'encrypted',
            'totp_recovery_codes' => 'encrypted:array',
            'totp_confirmed_at' => 'datetime',
            'totp_last_timestep' => 'integer',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPengaju(): bool
    {
        return $this->role === self::ROLE_PENGAJU;
    }

    public function isDonatur(): bool
    {
        return $this->role === self::ROLE_DONATUR;
    }

    /**
     * HMAC-SHA256 dari NIK, dipakai untuk mendeteksi satu identitas yang
     * dipakai banyak akun TANPA menyimpan NIK-nya di basis data.
     *
     * Awalan 'identity|' memisahkan domain penggunaan dari kode verifikasi
     * kuitansi, supaya dua fitur berbeda tidak menghasilkan nilai yang sama
     * walau memakai kunci rahasia yang sama.
     */
    public static function hashIdentityNumber(string $nik): string
    {
        $secret = (string) config('donasi.receipt_secret');

        if ($secret === '') {
            throw new \RuntimeException('DONASI_RECEIPT_SECRET belum diisi di file .env.');
        }

        return hash_hmac('sha256', 'identity|'.preg_replace('/\D/', '', $nik), $secret);
    }

    /** Rekening tujuan pencairan sudah lengkap? */
    public function hasPayoutAccount(): bool
    {
        return filled($this->bank_name)
            && filled($this->bank_account_number)
            && filled($this->bank_account_holder);
    }

    /** Tampilan aman untuk publik: BCA ****4821 a.n. AHMAD FAUZI */
    public function maskedPayoutAccount(): ?string
    {
        if (! $this->hasPayoutAccount()) {
            return null;
        }

        $nomor = preg_replace('/\s+/', '', (string) $this->bank_account_number);
        $ekor = substr($nomor, -4);

        return sprintf('%s %s%s a.n. %s',
            strtoupper($this->bank_name),
            str_repeat('*', max(0, strlen($nomor) - 4)),
            $ekor,
            $this->bank_account_holder,
        );
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Dua langkah dianggap aktif hanya bila kuncinya ada DAN sudah dibuktikan
     * terbaca oleh aplikasi authenticator. Kunci yang dibuat tapi tidak pernah
     * dikonfirmasi tidak boleh mengunci pemiliknya dari akunnya sendiri.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->totp_secret) && $this->totp_confirmed_at !== null;
    }

    /**
     * Versi untuk klaim di halaman PUBLIK.
     *
     * Sengaja hanya membaca totp_confirmed_at, supaya halaman publik cukup
     * memuat satu kolom itu — kunci rahasianya tidak perlu ditarik ke memori
     * sama sekali hanya untuk menampilkan sebuah lencana.
     *
     * Bedanya dengan hasTwoFactorEnabled(): yang itu dipakai gerbang sebelum
     * menghitung kode, jadi ia ikut memastikan kuncinya benar-benar ada.
     */
    public function twoFactorIsConfirmed(): bool
    {
        return $this->totp_confirmed_at !== null;
    }

    /** Sisa kode pemulihan yang belum terpakai. */
    public function unusedRecoveryCodeCount(): int
    {
        return count($this->totp_recovery_codes ?? []);
    }

    /** Hanya pengaju terverifikasi yang boleh mengajukan kampanye. */
    public function canSubmitCampaign(): bool
    {
        return $this->isPengaju() && $this->isVerified();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_PENGAJU => 'Pengaju Kampanye',
            default => 'Donatur',
        };
    }

    public function verificationLabel(): string
    {
        return match ($this->verification_status) {
            self::VERIFICATION_PENDING => 'Menunggu verifikasi',
            self::VERIFICATION_VERIFIED => 'Terverifikasi',
            self::VERIFICATION_REJECTED => 'Ditolak',
            default => 'Belum diverifikasi',
        };
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => route('admin.dashboard'),
            self::ROLE_PENGAJU => route('pengaju.dashboard'),
            default => route('donatur.dashboard'),
        };
    }
}
