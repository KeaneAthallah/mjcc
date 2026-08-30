<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Verifikasi Email Anda</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        .preheader { display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; mso-hide: all; }
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .brand-title { font-size: 18px !important; }
            .code-text { font-size: 34px !important; letter-spacing: 6px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
    <div class="preheader" lang="id">
        Gunakan kode {{ $code }} untuk memverifikasi alamat email Anda di Morowali Juara Command Center (MJCC).
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F1F5F9; padding: 24px 16px;">
        <tr>
            <td align="center" style="padding: 24px 0;">
                <table role="presentation" class="container" width="560" cellpadding="0" cellspacing="0" border="0" style="width: 560px; max-width: 560px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(17, 24, 39, 0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background-color: #065F46; background-image: linear-gradient(135deg, #065F46 0%, #047857 55%, #1E40AF 100%); padding: 28px 32px;" bgcolor="#065F46">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                            <tr>
                                                <td width="48" height="48" align="center" style="width: 48px; height: 48px; background-color: #ffffff; border-radius: 50%;">
                                                    <img src="{{ asset('logo.png') }}" alt="MJCC" width="34" height="34" style="display: block; width: 34px; height: 34px; border-radius: 50%;">
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin: 12px 0 2px 0; color: #ffffff; font-size: 17px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">MJCC</p>
                                        <p style="margin: 0; color: #D1FAE5; font-size: 12px; font-weight: 500;">Morowali Juara Command Center</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 32px 8px 32px;">
                            <h1 style="margin: 0 0 16px 0; color: #111827; font-size: 22px; font-weight: 800; line-height: 1.3;">Verifikasi Email Anda</h1>
                            <p style="margin: 0 0 12px 0; color: #4B5563; font-size: 14px; line-height: 1.6;">
                                Halo <strong style="color: #111827;">{{ $user->name }}</strong>,
                            </p>
                            <p style="margin: 0 0 12px 0; color: #4B5563; font-size: 14px; line-height: 1.6;">
                                Terima kasih telah mendaftar di <strong style="color: #111827;">Morowali Juara Command Center</strong>.
                                Gunakan kode di bawah ini untuk memverifikasi alamat email Anda:
                            </p>
                        </td>
                    </tr>

                    <!-- Code box -->
                    <tr>
                        <td align="center" style="padding: 12px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #ECFDF5; border: 2px dashed #34D399; border-radius: 12px;">
                                <tr>
                                    <td align="center" style="padding: 20px 16px;">
                                        <span class="code-text" style="font-size: 40px; font-weight: 800; letter-spacing: 8px; color: #047857; font-family: 'Courier New', Courier, monospace;">{{ $code }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Expiry + notice -->
                    <tr>
                        <td style="padding: 8px 32px 0 32px;">
                            <p style="margin: 0 0 12px 0; color: #4B5563; font-size: 14px; line-height: 1.6;">
                                Kode ini berlaku selama <strong style="color: #111827;">{{ $expiresInMinutes }} menit</strong>
                                dan hanya dapat digunakan <strong style="color: #111827;">satu kali</strong>.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 12px 14px; color: #1E40AF; font-size: 12px; line-height: 1.5;">
                                        Jika Anda tidak merasa melakukan pendaftaran, abaikan email ini. Untuk keamanan, jangan pernah membagikan kode verifikasi Anda kepada siapa pun.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table role="presentation" class="container" width="560" cellpadding="0" cellspacing="0" border="0" style="width: 560px; max-width: 560px;">
                    <tr>
                        <td align="center" style="padding: 20px 32px 8px 32px;">
                            <p style="margin: 0 0 4px 0; color: #6B7280; font-size: 12px; line-height: 1.6;">
                                Morowali Juara Command Center (MJCC)
                            </p>
                            <p style="margin: 0; color: #9CA3AF; font-size: 11px; line-height: 1.6;">
                                Pemerintah Kabupaten Morowali · Sulawesi Tengah
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>