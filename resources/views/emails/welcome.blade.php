<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur Pouire</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Pouire</div>
            <p>Marketplace de photos africaines</p>
        </div>

        <div class="content">
            <p class="greeting">Bonjour {{ $userName }} 👋</p>

            <p class="message">
                Nous sommes ravis de vous accueillir sur <strong>Pouire</strong>, la plateforme dédiée
                aux photos africaines de haute qualité !
            </p>

            @if($isPhotographer)
                <div class="highlight">
                    <strong>Compte Photographe</strong><br>
                    Votre profil photographe a été créé avec succès. Il est actuellement en attente
                    d'approbation par notre équipe. Vous recevrez un e-mail dès que votre profil
                    sera validé et que vous pourrez commencer à uploader vos photos.
                </div>

                <p class="message">
                    En attendant, n'hésitez pas à explorer notre plateforme et à découvrir le travail
                    d'autres photographes talentueux !
                </p>
            @else
                <div class="highlight">
                    <strong>Compte Acheteur</strong><br>
                    Vous pouvez dès maintenant explorer notre collection de photos et commencer
                    à télécharger celles qui vous inspirent !
                </div>

                <p class="message">
                    Découvrez des milliers de photos authentiques capturant la beauté, la culture
                    et la diversité du continent africain.
                </p>
            @endif

            <p class="message">
                <strong>Votre compte :</strong><br>
                Email : {{ $userEmail }}<br>
                Type : {{ $accountType === 'photographer' ? 'Photographe' : 'Acheteur' }}
            </p>

            <p class="message">
                Si vous avez des questions, n'hésitez pas à contacter notre équipe de support.
            </p>

            <p class="message">
                Merci d'avoir choisi Pouire !<br>
                L'équipe Pouire
            </p>
        </div>

        <div class="footer">
            <p>
                © {{ date('Y') }} Pouire. Tous droits réservés.<br>
                Cet email a été envoyé à {{ $userEmail }}
            </p>
        </div>
    </div>
</body>
</html>
