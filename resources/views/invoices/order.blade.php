<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice_number }}</title>
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
            margin-bottom: 10px;
        }
        .company-tagline {
            font-size: 10px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #15803d;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .invoice-date {
            font-size: 12px;
            color: #7f8c8d;
        }
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .address-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .address-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            color: #15803d;
        }
        .address-content {
            font-size: 12px;
            line-height: 1.8;
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
        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            margin-bottom: 0;
        }
        .totals td {
            padding: 8px;
            border-bottom: none;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 16px;
            background-color: #15803d;
            color: white;
        }
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #22c55e;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .highlight {
            color: #d97706;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="company-name">POUIRE</div>
                <div class="company-tagline">by TANGA GROUP - Photos professionnelles africaines</div>
                <div class="address-content">
                    {{ $company['address'] }}<br>
                    {{ $company['postal_code'] }} {{ $company['city'] }}<br>
                    {{ $company['country'] }}<br>
                    @if(isset($company['siret']))
                        RCCM: {{ $company['siret'] }}<br>
                    @endif
                    @if(isset($company['vat_number']))
                        IFU: {{ $company['vat_number'] }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">FACTURE</div>
                <div class="invoice-number">{{ $invoice_number }}</div>
                <div class="invoice-date">Date: {{ $invoice_date }}</div>
            </div>
        </div>

        <div class="addresses">
            <div class="address-block">
                <div class="address-title">Facturé à:</div>
                <div class="address-content">
                    {{ $customer['name'] }}<br>
                    {{ $customer['email'] }}
                </div>
            </div>
            <div class="address-block">
                <div class="address-title">Commande:</div>
                <div class="address-content">
                    N° de commande: {{ $order->order_number }}<br>
                    Date: {{ $order->created_at->format('d/m/Y') }}<br>
                    Statut: {{ ucfirst($order->status) }}
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Quantité</th>
                    <th class="text-right">Prix unitaire</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 0, ',', ' ') }} FCFA</td>
                    <td class="text-right">{{ number_format($item['total'], 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="clearfix">
            <div class="totals">
                <table>
                    <tr>
                        <td>Sous-total:</td>
                        <td class="text-right">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @if(isset($tax) && $tax > 0)
                    <tr>
                        <td>TVA (18%):</td>
                        <td class="text-right">{{ number_format($tax, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total TTC:</td>
                        <td class="text-right">{{ number_format($total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <p style="color: #15803d; font-weight: bold; font-size: 12px;">Merci pour votre achat sur POUIRE !</p>
            <p style="margin-top: 8px;">POUIRE by TANGA GROUP - La première plateforme de vente de photos professionnelles africaines</p>
            <p style="margin-top: 4px;">{{ $company['address'] }}, {{ $company['postal_code'] }} {{ $company['city'] }}, {{ $company['country'] }}</p>
            @if(isset($company['siret']))
                <p>RCCM: {{ $company['siret'] }} @if(isset($company['vat_number']))- IFU: {{ $company['vat_number'] }}@endif</p>
            @endif
            <p style="margin-top: 8px; font-size: 9px;">&copy; 2025 POUIRE by TANGA GROUP. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
