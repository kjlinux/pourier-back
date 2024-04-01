<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left, .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #15803d;
            margin-bottom: 5px;
        }
        .company-tagline {
            font-size: 10px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        .document-title {
            font-size: 28px;
            font-weight: bold;
            color: #15803d;
            margin-bottom: 5px;
        }
        .document-subtitle {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .photographer-info {
            background-color: #f0fdf4;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #22c55e;
        }
        .info-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
            color: #15803d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        thead {
            background-color: #15803d;
            color: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            font-weight: bold;
            font-size: 12px;
        }
        tbody tr:hover {
            background-color: #f0fdf4;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            background-color: #f0fdf4;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border: 1px solid #bbf7d0;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .summary-label, .summary-value {
            display: table-cell;
            padding: 5px;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            font-size: 18px;
            color: #15803d;
            border-top: 2px solid #22c55e;
            padding-top: 15px;
            margin-top: 15px;
        }
        .commission-info {
            background-color: #fffbeb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #f59e0b;
            font-size: 11px;
        }
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #22c55e;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="company-name">POUIRE</div>
                <div class="company-tagline">by TANGA GROUP</div>
                <div>Reçu de paiement photographe</div>
            </div>
            <div class="header-right">
                <div class="document-title">PAIEMENT</div>
                <div class="document-subtitle">{{ $payout_date }}</div>
            </div>
        </div>

        <div class="photographer-info">
            <div class="info-title">Informations photographe</div>
            <strong>{{ $photographer_name }}</strong><br>
            {{ $photographer_email }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Commande</th>
                    <th>Photo</th>
                    <th>Date vente</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['order_number'] }}</td>
                    <td>{{ $item['photo_title'] }}</td>
                    <td>{{ $item['sale_date'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="commission-info">
            <strong>Note:</strong> Les montants affichés correspondent à votre part nette après déduction de la commission POUIRE (20%).
        </div>

        <div class="summary">
            <div class="summary-row">
                <div class="summary-label">Nombre de ventes:</div>
                <div class="summary-value">{{ $total_items }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Période:</div>
                <div class="summary-value">{{ $period_start }} - {{ $period_end }}</div>
            </div>
            <div class="summary-row total-row">
                <div class="summary-label">Total versé:</div>
                <div class="summary-value">{{ number_format($total_amount, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        <div class="footer">
            <p>Ce document atteste du paiement des revenus pour la période indiquée.</p>
            <p style="margin-top: 8px;">POUIRE by TANGA GROUP - La première plateforme de vente de photos professionnelles africaines</p>
            <p style="margin-top: 8px; font-size: 9px;">&copy; 2025 POUIRE by TANGA GROUP. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
