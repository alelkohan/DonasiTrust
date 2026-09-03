<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan bahwa rekening tujuan pencairan diubah.
 *
 * Ini lapisan terakhir pada model "rekening terkunci". Kalau akun dibajak dan
 * rekeningnya diarahkan ke penyerang, surat inilah satu-satunya kesempatan
 * pemilik asli mengetahuinya SEBELUM admin meloloskan verifikasinya — karena
 * penyerang memegang sesi, bukan kotak masuk.
 *
 * Rekeningnya sengaja ditulis tersamar. Surat ini bisa terbaca di layar kunci
 * ponsel atau di kotak masuk yang ikut dibajak; empat digit terakhir sudah
 * cukup bagi pemiliknya untuk mengenali "ini bukan rekening saya".
 */
class PayoutAccountChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly ?string $rekeningTersamar) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rekening pencairan akun Anda diubah')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Rekening tujuan pencairan dana pada akun DonasiTrust Anda baru saja diubah menjadi:')
            ->line('**'.($this->rekeningTersamar ?? 'tidak tercatat').'**')
            ->line('Perubahan ini masih menunggu peninjauan admin. Selama belum ditinjau, '
                .'tidak ada dana yang bisa dicairkan.')
            ->line('**Kalau bukan Anda yang melakukannya**, segera ganti kata sandi akun Anda dan '
                .'hubungi admin — jangan tunggu peninjauan selesai.')
            ->salutation('Terima kasih, Tim DonasiTrust');
    }
}
