<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe - AfroLens</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .content {
            margin-top: 20px;
        }
        .greeting {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .message {
            color: #555;
            margin-bottom: 20px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .warning {
            background-color: #f8d7da;
            padding: 15px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">AfroLens</div>
            <p>Marketplace de photos africaines</p>
        </div>

        <div class="content">
            <p class="greeting">Bonjour 👋</p>

            <p class="message">
                Vous avez demandé la réinitialisation de votre mot de passe pour votre compte
                <strong>AfroLens</strong>.
            </p>

            <p class="message">
                Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :
            </p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Réinitialiser mon mot de passe</a>
            </div>

            <div class="highlight">
                <strong>⏱️ Lien valide pendant 60 minutes</strong><br>
                Ce lien de réinitialisation expirera dans 60 minutes pour des raisons de sécurité.
            </div>

            <p class="message">
                Si vous ne pouvez pas cliquer sur le bouton, copiez et collez ce lien dans votre navigateur :<br>
                <small style="word-break: break-all; color: #666;">{{ $resetUrl }}</small>
            </p>

            <div class="warning">
                <strong>⚠️ Vous n'avez pas fait cette demande ?</strong><br>
                Si vous n'avez pas demandé de réinitialisation de mot de passe, vous pouvez ignorer
                cet email en toute sécurité. Votre mot de passe restera inchangé.
            </div>

            <p class="message">
                Merci de faire partie de la communauté AfroLens !<br>
                L'équipe AfroLens
            </p>
        </div>

        <div class="footer">
            <p>
                © {{ date('Y') }} AfroLens. Tous droits réservés.<br>
                Cet email a été envoyé à {{ $email }}
            </p>
        </div>
    </div>
</body>
</html>
