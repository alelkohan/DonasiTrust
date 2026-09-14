<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi DonasiTrust</title>
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
            .otp-box {
                background-color: #231f36 !important;
                border-color: #99ff04 !important;
            }
            .otp-label, .otp-code {
                color: #99ff04 !important;
            }
            .otp-validity {
                color: #94a3b8 !important;
            }
            .warning-box {
                background-color: rgba(245, 158, 11, 0.1) !important;
                border-color: rgba(245, 158, 11, 0.3) !important;
                color: #fcd34d !important;
            }
            .warning-text {
                color: #fcd34d !important;
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
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-card" style="max-width: 560px; background-color: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        {{-- Header Bar dengan anti-dimming gradient --}}
        <tr>
            <td style="padding: 24px 32px; background-color: #99ff04; background-image: linear-gradient(#99ff04, #99ff04); text-align: left;">
                <span style="font-size: 22px; font-weight: 900; color: #000000; letter-spacing: -0.5px;">
                    DonasiTrust
                </span>
            </td>
        </tr>

        {{-- Body Content --}}
        <tr>
            <td style="padding: 32px;">
                <h2 class="text-heading" style="margin: 0 0 12px; font-size: 20px; font-weight: 800; color: #0f172a;">
                    Halo, {{ $userName }}!
                </h2>
                <p class="text-body" style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                    Anda menerima pesan ini karena ada permintaan untuk <strong class="text-heading" style="color: #0f172a;">{{ $purposeLabel }}</strong> pada akun DonasiTrust Anda.
                </p>

                {{-- Box OTP --}}
                <div class="otp-box" style="margin: 24px 0; padding: 22px; background-color: #f0fdf4; border: 2px dashed #99ff04; border-radius: 16px; text-align: center;">
                    <p class="otp-label" style="margin: 0 0 8px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #065f46;">
                        Kode Verifikasi (OTP)
                    </p>
                    <div class="otp-code" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #065f46; margin: 6px 0;">
                        {{ $code }}
                    </div>
                    <p class="otp-validity" style="margin: 8px 0 0; font-size: 12px; color: #64748b;">
                        Berlaku selama <strong class="text-heading" style="color: #0f172a;">{{ $validMinutes }} menit</strong>.
                    </p>
                </div>

                {{-- Box Peringatan Keamanan --}}
                <div class="warning-box" style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;">
                    <p class="warning-text" style="margin: 0; font-size: 12px; line-height: 1.6; color: #92400e;">
                        <strong style="color: #78350f;">Peringatan Keamanan:</strong> Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai admin DonasiTrust. Jika Anda tidak merasa melakukan aksi ini, segera periksa keamanan akun Anda.
                    </p>
                </div>

                <p class="text-body" style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.6;">
                    Salam hangat,<br>
                    <strong class="text-heading" style="color: #0f172a;">Tim DonasiTrust</strong>
                </p>
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td class="footer-bg" style="padding: 18px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center;">
                Email ini dikirim otomatis oleh sistem DonasiTrust untuk memastikan keamanan akun Anda.
            </td>
        </tr>
    </table>
</body>
</html>
