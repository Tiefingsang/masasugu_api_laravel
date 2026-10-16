<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation — Masasugu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #FFF4E6 0%, #FFFFFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1A1A1A;
        }
        .card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(255, 107, 0, 0.1);
            text-align: center;
        }
        .icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #FF6B00 0%, #FF8B3D 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 40px;
        }
        h1 { font-size: 24px; margin-bottom: 12px; }
        p { color: #666; line-height: 1.6; margin-bottom: 24px; font-size: 15px; }
        .token-box {
            background: #F9FAFB;
            padding: 12px 16px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 12px;
            color: #FF6B00;
            word-break: break-all;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #FF6B00 0%, #FF8B3D 100%);
            color: #FFFFFF;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔐</div>
        <h1>Réinitialisation de mot de passe</h1>
        <p>Ouvrez l'application Masasugu pour définir un nouveau mot de passe.</p>

        <div class="token-box">
            Token : {{ $token }}
        </div>

        <a href="masasugu://reset-password?token={{ $token }}" class="btn">
            Ouvrir l'application
        </a>
    </div>
</body>
</html>
