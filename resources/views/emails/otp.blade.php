<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi DonasiTrust</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <tr>
            <td style="padding: 28px 32px; background-color: #047857; text-align: left;">
                <span style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">
                    Donasi<span style="color: #6ee7b7;">Trust</span>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 32px;">
                <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 700; color: #0f172a;">
                    Halo, {{ $userName }}!
                </h2>
                <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                    Anda menerima pesan ini karena ada permintaan untuk <strong>{{ $purposeLabel }}</strong> pada akun DonasiTrust Anda.
                </p>

                <div style="margin: 24px 0; padding: 20px; background-color: #f0fdf4; border: 1px dashed #86efac; border-radius: 12px; text-align: center;">
                    <p style="margin: 0 0 8px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #166534;">
                        Kode Verifikasi (OTP)
                    </p>
                    <div style="font-family: monospace; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #065f46;">
                        {{ $code }}
                    </div>
                    <p style="margin: 8px 0 0; font-size: 12px; color: #166534;">
                        Berlaku selama <strong>{{ $validMinutes }} menit</strong>.
                    </p>
                </div>

                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px;">
                    <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #92400e;">
                        <strong>Peringatan Keamanan:</strong> Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai admin DonasiTrust. Jika Anda tidak merasa melakukan aksi ini, segera periksa keamanan akun Anda.
                    </p>
                </div>

                <p style="margin: 0; font-size: 13px; color: #64748b;">
                    Salam hangat,<br>
                    <strong>Tim DonasiTrust</strong>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center;">
                Email ini dikirim otomatis oleh sistem DonasiTrust untuk memastikan keamanan akun Anda.
            </td>
        </tr>
    </table>
</body>
</html>
