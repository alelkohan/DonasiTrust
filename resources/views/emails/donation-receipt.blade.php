<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Digital &amp; Ucapan Terima Kasih — DonasiTrust</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        {{-- Header --}}
        <tr>
            <td style="padding: 28px 32px; background-color: #047857; text-align: left;">
                <span style="font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">
                    Donasi<span style="color: #6ee7b7;">Trust</span>
                </span>
                <p style="margin: 4px 0 0; font-size: 12px; color: #a7f3d0; font-weight: 500;">
                    Platform Donasi Transparan &amp; Terverifikasi
                </p>
            </td>
        </tr>

        {{-- Body Content --}}
        <tr>
            <td style="padding: 32px;">
                {{-- Ucapan Terima Kasih --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; background-color: #d1fae5; border-radius: 50%; color: #047857; font-size: 28px; margin-bottom: 12px;">
                        ♥
                    </div>
                    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 800; color: #0f172a;">
                        Terima Kasih atas Donasi Anda!
                    </h2>
                    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
                        Halo <strong>{{ $donation->displayName() }}</strong>, donasi Anda sebesar <strong style="color: #047857;">{{ rupiah($donation->amount) }}</strong> telah berhasil kami terima dan dicatat secara otomatis dalam ledger transparansi DonasiTrust.
                    </p>
                </div>

                {{-- Detail Donasi Table --}}
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                        Rincian Transaksi Donasi
                    </h3>
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 13px; color: #334155;">
                        <tr>
                            <td style="padding: 6px 0; color: #64748b;">No. Referensi:</td>
                            <td style="padding: 6px 0; text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">{{ $donation->reference }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #64748b;">Kampanye:</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #0f172a;">{{ $donation->campaign->title }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #64748b;">Nominal Donasi:</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 800; color: #047857; font-size: 15px;">{{ rupiah($donation->amount) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #64748b;">Waktu Pembayaran:</td>
                            <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #334155;">{{ $donation->paid_at ? $donation->paid_at->translatedFormat('d F Y, H:i') . ' WIB' : '—' }}</td>
                        </tr>
                        @if($donation->message)
                        <tr>
                            <td colspan="2" style="padding: 12px 0 0; border-top: 1px dashed #cbd5e1; margin-top: 6px;">
                                <p style="margin: 0; font-size: 12px; font-style: italic; color: #475569;">
                                    &ldquo;{{ $donation->message }}&rdquo;
                                </p>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Segel Kuintansi & HMAC --}}
                <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 18px; margin-bottom: 24px;">
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #065f46; margin-bottom: 6px; letter-spacing: 0.5px;">
                        ✓ Segel Kriptografi HMAC-SHA256
                    </div>
                    <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.5; color: #047857;">
                        Catatan transaksi ini dilindungi segel digital HMAC-SHA256 untuk menjamin integritas data dan transparansi penyaluran.
                    </p>
                    <div style="font-family: monospace; font-size: 10px; word-break: break-all; background-color: #ffffff; padding: 8px 12px; border-radius: 6px; border: 1px solid #6ee7b7; color: #064e3b;">
                        {{ $donation->verification_code }}
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ route('kuitansi.show', $donation) }}" target="_blank" style="display: inline-block; background-color: #047857; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 24px; border-radius: 50px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        Cetak Kuitansi Digital (PDF) &rarr;
                    </a>
                </div>

                <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #64748b;">
                    Setiap pencairan dan penggunaan dana untuk kampanye ini akan dilaporkan secara transparan. Anda dapat memantau penggunaannya kapan saja melalui portal DonasiTrust.<br><br>
                    Salam hangat,<br>
                    <strong>Tim DonasiTrust</strong>
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="padding: 20px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; line-height: 1.5;">
                Email ini dikirim otomatis oleh DonasiTrust sebagai kuitansi dan konfirmasi donasi resmi.<br>
                &copy; {{ date('Y') }} DonasiTrust. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
