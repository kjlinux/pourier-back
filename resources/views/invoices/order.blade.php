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
            font-family: 'DejaVu Sans', Arial, sans-serif;
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
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
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
            color: #2c3e50;
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
            background-color: #2c3e50;
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
            background-color: #f8f9fa;
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
            background-color: #2c3e50;
            color: white;
        }
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="address-content">
                    {{ $company['address'] }}<br>
                    {{ $company['postal_code'] }} {{ $company['city'] }}<br>
                    {{ $company['country'] }}<br>
                    @if(isset($company['siret']))
                        SIRET: {{ $company['siret'] }}<br>
                    @endif
                    @if(isset($company['vat_number']))
                        TVA: {{ $company['vat_number'] }}
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
                    <td class="text-right">{{ number_format($item['unit_price'], 2) }} €</td>
                    <td class="text-right">{{ number_format($item['total'], 2) }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="clearfix">
            <div class="totals">
                <table>
                    <tr>
                        <td>Sous-total:</td>
                        <td class="text-right">{{ number_format($subtotal, 2) }} €</td>
                    </tr>
                    <tr>
                        <td>TVA (20%):</td>
                        <td class="text-right">{{ number_format($tax, 2) }} €</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total TTC:</td>
                        <td class="text-right">{{ number_format($total, 2) }} €</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>Merci pour votre achat !</p>
            <p>{{ $company['name'] }} - {{ $company['address'] }}, {{ $company['postal_code'] }} {{ $company['city'] }}</p>
            @if(isset($company['siret']))
                <p>SIRET: {{ $company['siret'] }} @if(isset($company['vat_number']))- TVA: {{ $company['vat_number'] }}@endif</p>
            @endif
        </div>
    </div>
</body>
</html>
