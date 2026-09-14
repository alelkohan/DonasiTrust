<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi DonasiTrust</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0e0c16; margin: 0; padding: 24px; color: #f8fafc;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #1b182a; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.12); overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);">
        <tr>
            <td style="padding: 24px 32px; background-color: #13111c; border-bottom: 1px solid rgba(255, 255, 255, 0.08); text-align: left;">
                <span style="font-size: 22px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px;">
                    Donasi<span style="color: #99ff04;">Trust</span>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 32px;">
                <h2 style="margin: 0 0 12px; font-size: 20px; font-weight: 800; color: #ffffff;">
                    Halo, {{ $userName }}!
                </h2>
                <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #cbd5e1;">
                    Anda menerima pesan ini karena ada permintaan untuk <strong style="color: #ffffff;">{{ $purposeLabel }}</strong> pada akun DonasiTrust Anda.
                </p>

                <div style="margin: 24px 0; padding: 22px; background-color: #231f36; border: 1.5px dashed #99ff04; border-radius: 16px; text-align: center;">
                    <p style="margin: 0 0 8px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #99ff04;">
                        Kode Verifikasi (OTP)
                    </p>
                    <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #99ff04; margin: 6px 0;">
                        {{ $code }}
                    </div>
                    <p style="margin: 8px 0 0; font-size: 12px; color: #94a3b8;">
                        Berlaku selama <strong style="color: #ffffff;">{{ $validMinutes }} menit</strong>.
                    </p>
                </div>

                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;">
                    <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #fcd34d;">
                        <strong style="color: #fbbf24;">Peringatan Keamanan:</strong> Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku sebagai admin DonasiTrust. Jika Anda tidak merasa melakukan aksi ini, segera amankan akun Anda.
                    </p>
                </div>

                <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.6;">
                    Salam hangat,<br>
                    <strong style="color: #ffffff;">Tim DonasiTrust</strong>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding: 18px 32px; background-color: #13111c; border-top: 1px solid rgba(255, 255, 255, 0.08); font-size: 11px; color: #64748b; text-align: center;">
                Email ini dikirim otomatis oleh sistem DonasiTrust untuk memastikan keamanan akun Anda.
            </td>
        </tr>
    </table>
</body>
</html>
