<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Disbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisbursementReleasedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public Disbursement $disbursement
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 Update Pencairan Dana: '.mb_strimwidth($this->campaign->title, 0, 40, '...'),
        );
    }

    public function content(): Content
    {
        $transparencyUrl = route('kampanye.transparansi', $this->campaign->slug);

        return new Content(
            htmlString: '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 6px; border: 1px solid #bbf7d0;">
                        <h3 style="color: #166534; margin: 0;">Dana Kampanye Telah Dicairkan oleh Admin</h3>
                    </div>
                    <p style="margin-top: 15px; color: #374151;">Halo Donatur DonasiTrust,</p>
                    <p style="color: #374151;">Kami ingin memberitahukan bahwa dana donasi Anda pada kampanye <strong>'.e($this->campaign->title).'</strong> telah dicairkan secara bertahap kepada penggalang dana.</p>

                    <div style="background-color: #f9fafb; padding: 15px; border-radius: 6px; margin: 15px 0;">
                        <p style="margin: 4px 0;"><strong>Nominal Dicairkan:</strong> '.rupiah($this->disbursement->amount).'</p>
                        <p style="margin: 4px 0;"><strong>Keperluan / Tahap:</strong> '.e($this->disbursement->purpose).'</p>
                        <p style="margin: 4px 0;"><strong>Rekening Tujuan:</strong> '.e($this->disbursement->maskedPayee()).'</p>
                        <p style="margin: 4px 0;"><strong>Tanggal Cair:</strong> '.$this->disbursement->released_at?->translatedFormat('d F Y, H:i').'</p>
                    </div>

                    <p style="color: #374151;">Seluruh bukti transfer dan catatan audit ber-rantai dapat Anda pantau secara publik di halaman transparansi kami.</p>

                    <div style="text-align: center; margin-top: 25px;">
                        <a href="'.$transparencyUrl.'" style="background-color: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; inline-block;">Lihat Transparansi Kampanye</a>
                    </div>
                </div>
            ',
        );
    }
}
