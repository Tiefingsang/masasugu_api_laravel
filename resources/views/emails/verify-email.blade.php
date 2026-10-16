<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérifiez votre email</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #F5F5F5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background: linear-gradient(135deg, #FF6B00 0%, #FF8B3D 100%); padding: 40px 30px; text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 12px;">📧</div>
                            <h1 style="color: #FFFFFF; margin: 0; font-size: 24px; font-weight: 700;">
                                Vérifiez votre email
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 30px;">
                            <p style="font-size: 16px; color: #333; line-height: 1.6;">
                                Bonjour <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="font-size: 15px; color: #666; line-height: 1.6; margin-top: 16px;">
                                Merci de vous être inscrit sur <strong>Masasugu</strong> ! 🎉
                            </p>

                            <p style="font-size: 15px; color: #666; line-height: 1.6; margin-top: 16px;">
                                Pour activer toutes les fonctionnalités, veuillez vérifier votre email :
                            </p>

                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ $verifyUrl }}"
                                   style="display: inline-block; background: linear-gradient(135deg, #FF6B00 0%, #FF8B3D 100%); color: #FFFFFF; padding: 16px 40px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 16px;">
                                    ✅ Vérifier mon email
                                </a>
                            </div>

                            <p style="font-size: 13px; color: #999; line-height: 1.6;">
                                Si le bouton ne fonctionne pas, copiez ce lien :
                            </p>

                            <p style="font-size: 12px; color: #FF6B00; word-break: break-all; background: #F9FAFB; padding: 12px; border-radius: 8px;">
                                {{ $verifyUrl }}
                            </p>

                            <p style="font-size: 13px; color: #999; line-height: 1.6; margin-top: 24px;">
                                ⚠️ Ce lien expire dans 60 minutes.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #F9FAFB; padding: 20px 30px; text-align: center; color: #9CA3AF; font-size: 12px;">
                            © {{ date('Y') }} Masasugu — Tous droits réservés
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
