<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kuitansi Digital Terverifikasi DonasiTrust - #'.$this->donation->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; rounded-lg: 8px;">
                    <h2 style="color: #059669;">Kuitansi Digital Terverifikasi</h2>
                    <p>Terima kasih atas donasi Anda di DonasiTrust!</p>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>No. Transaksi:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">'.$this->donation->reference.'</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Kampanye:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">'.e($this->donation->campaign->title).'</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Nominal:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">'.rupiah($this->donation->amount).'</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Kode HMAC:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee; font-family: monospace; font-size: 11px;">'.$this->donation->verification_code.'</td></tr>
                    </table>
                    <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">Kode HMAC di atas dapat diverifikasi keasliannya kapan saja tanpa perlu login di <a href="'.route('verifikasi.form').'">'.route('verifikasi.form').'</a>.</p>
                </div>
            ',
        );
    }
}
