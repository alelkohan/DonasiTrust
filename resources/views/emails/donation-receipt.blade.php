<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Digital &amp; Ucapan Terima Kasih — DonasiTrust</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0e0c16; margin: 0; padding: 24px; color: #f8fafc;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #1b182a; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.12); overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);">
        {{-- Header --}}
        <tr>
            <td style="padding: 24px 32px; background-color: #13111c; border-bottom: 1px solid rgba(255, 255, 255, 0.08); text-align: left;">
                <span style="font-size: 22px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px;">
                    Donasi<span style="color: #99ff04;">Trust</span>
                </span>
                <p style="margin: 4px 0 0; font-size: 12px; color: #94a3b8; font-weight: 500;">
                    Platform Donasi Transparan &amp; Terverifikasi
                </p>
            </td>
        </tr>

        {{-- Body Content --}}
        <tr>
            <td style="padding: 32px;">
                {{-- Ucapan Terima Kasih --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; background-color: rgba(153, 255, 4, 0.15); border: 1px solid rgba(153, 255, 4, 0.3); border-radius: 50%; color: #99ff04; font-size: 26px; margin-bottom: 12px;">
                        ♥
                    </div>
                    <h2 style="margin: 0 0 8px; font-size: 20px; font-weight: 900; color: #ffffff;">
                        Terima Kasih atas Donasi Anda!
                    </h2>
                    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
                        Halo <strong style="color: #ffffff;">{{ $donation->displayName() }}</strong>, donasi Anda sebesar <strong style="color: #99ff04;">{{ rupiah($donation->amount) }}</strong> telah berhasil kami terima dan dicatat secara otomatis dalam ledger transparansi DonasiTrust.
                    </p>
                </div>

                {{-- Detail Donasi Table --}}
                <div style="background-color: #231f36; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 800; color: #ffffff; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 10px;">
                        Rincian Transaksi Donasi
                    </h3>
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 13px; color: #cbd5e1;">
                        <tr>
                            <td style="padding: 7px 0; color: #94a3b8;">No. Referensi:</td>
                            <td style="padding: 7px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #ffffff;">#{{ $donation->reference }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; color: #94a3b8;">Kampanye:</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 700; color: #ffffff;">{{ $donation->campaign->title }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; color: #94a3b8;">Nominal Donasi:</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 900; color: #99ff04; font-size: 15px;">{{ rupiah($donation->amount) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; color: #94a3b8;">Waktu Pembayaran:</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #e2e8f0;">{{ $donation->paid_at ? $donation->paid_at->translatedFormat('d F Y, H:i') . ' WIB' : '—' }}</td>
                        </tr>
                        @if($donation->message)
                        <tr>
                            <td colspan="2" style="padding: 12px 0 0; border-top: 1px dashed rgba(255, 255, 255, 0.1); margin-top: 6px;">
                                <p style="margin: 0; font-size: 12px; font-style: italic; color: #cbd5e1;">
                                    &ldquo;{{ $donation->message }}&rdquo;
                                </p>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Segel Kuintansi & HMAC --}}
                <div style="background-color: rgba(153, 255, 4, 0.05); border: 1px solid rgba(153, 255, 4, 0.25); border-radius: 14px; padding: 18px; margin-bottom: 24px;">
                    <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #99ff04; margin-bottom: 6px; letter-spacing: 0.5px;">
                        ✓ Segel Kriptografi HMAC-SHA256
                    </div>
                    <p style="margin: 0 0 10px; font-size: 12px; line-height: 1.5; color: #cbd5e1;">
                        Catatan transaksi ini dilindungi segel digital HMAC-SHA256 untuk menjamin integritas data dan transparansi penyaluran.
                    </p>
                    <div style="font-family: ui-monospace, monospace; font-size: 11px; word-break: break-all; background-color: #12101c; padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(153, 255, 4, 0.3); color: #99ff04; font-weight: bold;">
                        {{ $donation->verification_code }}
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ route('kuitansi.show', $donation) }}" target="_blank" style="display: inline-block; background-color: #99ff04; color: #000000; text-decoration: none; font-size: 14px; font-weight: 900; padding: 12px 28px; border-radius: 50px; box-shadow: 0 4px 14px rgba(153, 255, 4, 0.3);">
                        Cetak Kuitansi Digital (PDF) &rarr;
                    </a>
                </div>

                <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #94a3b8;">
                    Setiap pencairan dan penggunaan dana untuk kampanye ini akan dilaporkan secara transparan. Anda dapat memantau penggunaannya kapan saja melalui portal DonasiTrust.<br><br>
                    Salam hangat,<br>
                    <strong style="color: #ffffff;">Tim DonasiTrust</strong>
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="padding: 18px 32px; background-color: #13111c; border-top: 1px solid rgba(255, 255, 255, 0.08); font-size: 11px; color: #64748b; text-align: center; line-height: 1.5;">
                Email ini dikirim otomatis oleh DonasiTrust sebagai kuitansi dan konfirmasi donasi resmi.<br>
                &copy; {{ date('Y') }} DonasiTrust. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
