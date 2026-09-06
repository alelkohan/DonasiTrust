<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\ExpenseReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
include_once __DIR__.'/../../Support/helpers.php';
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpenseReportVerifiedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public ExpenseReport $expenseReport
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 Laporan Penggunaan Dana (LPJ): '.mb_strimwidth($this->campaign->title, 0, 40, '...'),
        );
    }

    public function content(): Content
    {
        $transparencyUrl = route('kampanye.transparansi', $this->campaign->slug);

        return new Content(
            htmlString: '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <div style="background-color: #eff6ff; padding: 15px; border-radius: 6px; border: 1px solid #bfdbfe;">
                        <h3 style="color: #1e40af; margin: 0;">Laporan Penggunaan Dana (LPJ) Telah Diverifikasi</h3>
                    </div>
                    <p style="margin-top: 15px; color: #374151;">Halo Donatur DonasiTrust,</p>
                    <p style="color: #374151;">Penggalang dana kampanye <strong>'.e($this->campaign->title).'</strong> telah mengunggah Laporan Pertanggungjawaban (LPJ) beserta bukti nota belanja yang telah divalidasi oleh Admin.</p>

                    <div style="background-color: #f9fafb; padding: 15px; border-radius: 6px; margin: 15px 0;">
                        <p style="margin: 4px 0;"><strong>Judul Pengeluaran:</strong> '.e($this->expenseReport->title).'</p>
                        <p style="margin: 4px 0;"><strong>Nominal Terpakai:</strong> '.rupiah($this->expenseReport->amount).'</p>
                        <p style="margin: 4px 0;"><strong>Rincian:</strong> '.e($this->expenseReport->description).'</p>
                        <p style="margin: 4px 0;"><strong>Tanggal Pengeluaran:</strong> '.$this->expenseReport->spent_on?->translatedFormat('d F Y').'</p>
                    </div>

                    <p style="color: #374151;">Anda dapat memeriksa foto nota fisik dan dokumen pendukung secara terbuka di halaman transparansi.</p>

                    <div style="text-align: center; margin-top: 25px;">
                        <a href="'.$transparencyUrl.'" style="background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; inline-block;">Periksa Nota & LPJ Kampanye</a>
                    </div>
                </div>
            ',
        );
    }
}
