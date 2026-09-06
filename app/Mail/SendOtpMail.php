<?php

namespace App\Mail;

use App\Models\EmailOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public string $userName,
    ) {}

    public function envelope(): Envelope
    {
        $label = EmailOtp::purposeLabel($this->purpose);

        return new Envelope(
            subject: "[DonasiTrust] Kode Verifikasi: {$this->code} - {$label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'code' => $this->code,
                'purposeLabel' => EmailOtp::purposeLabel($this->purpose),
                'userName' => $this->userName,
                'validMinutes' => EmailOtp::EXPIRATION_MINUTES,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
