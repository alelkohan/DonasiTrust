<?php

namespace Tests\Unit;

use App\Mail\SendOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OtpService::class);
    }

    public function test_generate_and_send_creates_otp_and_sends_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        $otp = $this->service->generateAndSend($user, EmailOtp::PURPOSE_DISBURSEMENT_REQUEST);

        $this->assertDatabaseHas('email_otps', [
            'id' => $otp->id,
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'purpose' => EmailOtp::PURPOSE_DISBURSEMENT_REQUEST,
            'attempts' => 0,
        ]);

        Mail::assertSent(SendOtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && strlen($mail->code) === 6
                && $mail->purpose === EmailOtp::PURPOSE_DISBURSEMENT_REQUEST;
        });
    }

    public function test_generate_and_send_enforces_cooldown(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->service->generateAndSend($user, EmailOtp::PURPOSE_DISBURSEMENT_REQUEST);

        $this->expectException(ValidationException::class);
        $this->service->generateAndSend($user, EmailOtp::PURPOSE_DISBURSEMENT_REQUEST);
    }

    public function test_assert_valid_accepts_correct_code(): void
    {
        $user = User::factory()->create();

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailOtp::PURPOSE_BANK_CHANGE,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->service->assertValid($user, EmailOtp::PURPOSE_BANK_CHANGE, '123456');

        // OTP langsung dihapus setelah berhasil diverifikasi
        $this->assertDatabaseMissing('email_otps', [
            'user_id' => $user->id,
            'purpose' => EmailOtp::PURPOSE_BANK_CHANGE,
        ]);
    }

    public function test_assert_valid_rejects_incorrect_code_and_tracks_attempts(): void
    {
        $user = User::factory()->create();

        $otp = EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailOtp::PURPOSE_BANK_CHANGE,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        try {
            $this->service->assertValid($user, EmailOtp::PURPOSE_BANK_CHANGE, '000000');
            $this->fail('Harusnya gagal');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Kode verifikasi salah', $e->getMessage());
        }

        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_assert_valid_fails_and_deletes_when_attempts_exceeded(): void
    {
        $user = User::factory()->create();

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailOtp::PURPOSE_BANK_CHANGE,
            'code_hash' => Hash::make('123456'),
            'attempts' => EmailOtp::MAX_ATTEMPTS,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->assertValid($user, EmailOtp::PURPOSE_BANK_CHANGE, '123456');
    }

    public function test_assert_valid_rejects_expired_code(): void
    {
        $user = User::factory()->create();

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => EmailOtp::PURPOSE_BANK_CHANGE,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->assertValid($user, EmailOtp::PURPOSE_BANK_CHANGE, '123456');
    }
}
