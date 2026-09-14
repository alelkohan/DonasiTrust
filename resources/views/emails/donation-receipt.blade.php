<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Digital &amp; Ucapan Terima Kasih — DonasiTrust</title>
    <style>
        @media (prefers-color-scheme: dark) {
            body, .body-bg {
                background-color: #0e0c16 !important;
                color: #f8fafc !important;
            }
            .email-card {
                background-color: #1b182a !important;
                border-color: rgba(255, 255, 255, 0.12) !important;
            }
            .text-heading {
                color: #ffffff !important;
            }
            .text-body {
                color: #cbd5e1 !important;
            }
            .text-muted {
                color: #94a3b8 !important;
            }
            .receipt-detail {
                background-color: #231f36 !important;
                border-color: rgba(255, 255, 255, 0.08) !important;
            }
            .receipt-amount {
                color: #99ff04 !important;
            }
            .hmac-box {
                background-color: rgba(153, 255, 4, 0.05) !important;
                border-color: rgba(153, 255, 4, 0.25) !important;
            }
            .hmac-title {
                color: #99ff04 !important;
            }
            .hmac-code {
                background-color: #12101c !important;
                border-color: rgba(153, 255, 4, 0.3) !important;
                color: #99ff04 !important;
            }
            .footer-bg {
                background-color: #13111c !important;
                border-color: rgba(255, 255, 255, 0.08) !important;
                color: #64748b !important;
            }
        }
    </style>
</head>
<body class="body-bg" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-card" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);">
        {{-- Header Bar --}}
        <tr>
            <td style="padding: 24px 32px; background-color: #99ff04; background-image: linear-gradient(#99ff04, #99ff04); text-align: left;">
                <span style="font-size: 22px; font-weight: 900; color: #000000; letter-spacing: -0.5px;">
                    DonasiTrust
                </span>
                <p style="margin: 4px 0 0; font-size: 12px; color: #000000; font-weight: 600;">
                    Platform Donasi Transparan &amp; Terverifikasi
                </p>
            </td>
        </tr>

        {{-- Body Content --}}
        <tr>
            <td style="padding: 32px;">
                {{-- Ucapan Terima Kasih --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; background-color: rgba(153, 255, 4, 0.2); border: 1px solid rgba(153, 255, 4, 0.4); border-radius: 50%; color: #065f46; font-size: 26px; margin-bottom: 12px;">
                        ♥
                    </div>
                    <h2 class="text-heading" style="margin: 0 0 8px; font-size: 20px; font-weight: 900; color: #0f172a;">
                        Terima Kasih atas Donasi Anda!
                    </h2>
                    <p class="text-body" style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
                        Halo <strong class="text-heading" style="color: #0f172a;">{{ $donation->displayName() }}</strong>, donasi Anda sebesar <strong class="receipt-amount" style="color: #065f46; font-weight: 900;">{{ rupiah($donation->amount) }}</strong> telah berhasil kami terima dan dicatat secara otomatis dalam ledger transparansi DonasiTrust.
                    </p>
                </div>

                {{-- Detail Donasi Table --}}
                <div class="receipt-detail" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                    <h3 class="text-heading" style="margin: 0 0 14px; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                        Rincian Transaksi Donasi
                    </h3>
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size: 13px; color: #475569;">
                        <tr>
                            <td class="text-muted" style="padding: 7px 0; color: #64748b;">No. Referensi:</td>
                            <td class="text-heading" style="padding: 7px 0; text-align: right; font-family: ui-monospace, monospace; font-weight: 700; color: #0f172a;">#{{ $donation->reference }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="padding: 7px 0; color: #64748b;">Kampanye:</td>
                            <td class="text-heading" style="padding: 7px 0; text-align: right; font-weight: 700; color: #0f172a;">{{ $donation->campaign->title }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="padding: 7px 0; color: #64748b;">Nominal Donasi:</td>
                            <td class="receipt-amount" style="padding: 7px 0; text-align: right; font-weight: 900; color: #065f46; font-size: 15px;">{{ rupiah($donation->amount) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="padding: 7px 0; color: #64748b;">Waktu Pembayaran:</td>
                            <td class="text-body" style="padding: 7px 0; text-align: right; font-weight: 600; color: #334155;">{{ $donation->paid_at ? $donation->paid_at->translatedFormat('d F Y, H:i') . ' WIB' : '—' }}</td>
                        </tr>
                        @if($donation->message)
                        <tr>
                            <td colspan="2" style="padding: 12px 0 0; border-top: 1px dashed #e2e8f0; margin-top: 6px;">
                                <p class="text-body" style="margin: 0; font-size: 12px; font-style: italic; color: #475569;">
                                    &ldquo;{{ $donation->message }}&rdquo;
                                </p>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Segel Kuintansi & HMAC --}}
                <div class="hmac-box" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 18px; margin-bottom: 24px;">
                    <div class="hmac-title" style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #065f46; margin-bottom: 6px; letter-spacing: 0.5px;">
                        ✓ Segel Kriptografi HMAC-SHA256
                    </div>
                    <p class="text-body" style="margin: 0 0 10px; font-size: 12px; line-height: 1.5; color: #475569;">
                        Catatan transaksi ini dilindungi segel digital HMAC-SHA256 untuk menjamin integritas data dan transparansi penyaluran.
                    </p>
                    <div class="hmac-code" style="font-family: ui-monospace, monospace; font-size: 11px; word-break: break-all; background-color: #ffffff; padding: 10px 14px; border-radius: 8px; border: 1px solid #86efac; color: #065f46; font-weight: bold;">
                        {{ $donation->verification_code }}
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ route('kuitansi.show', $donation) }}" target="_blank" style="display: inline-block; background-color: #99ff04; color: #000000; text-decoration: none; font-size: 14px; font-weight: 900; padding: 12px 28px; border-radius: 50px; box-shadow: 0 4px 14px rgba(153, 255, 4, 0.3);">
                        Cetak Kuitansi Digital (PDF) &rarr;
                    </a>
                </div>

                <p class="text-muted" style="margin: 0; font-size: 13px; line-height: 1.6; color: #64748b;">
                    Setiap pencairan dan penggunaan dana untuk kampanye ini akan dilaporkan secara transparan. Anda dapat memantau penggunaannya kapan saja melalui portal DonasiTrust.<br><br>
                    Salam hangat,<br>
                    <strong class="text-heading" style="color: #0f172a;">Tim DonasiTrust</strong>
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td class="footer-bg" style="padding: 18px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; line-height: 1.5;">
                Email ini dikirim otomatis oleh DonasiTrust sebagai kuitansi dan konfirmasi donasi resmi.<br>
                &copy; {{ date('Y') }} DonasiTrust. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
