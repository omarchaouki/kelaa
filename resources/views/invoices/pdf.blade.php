<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c1917; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { vertical-align: top; padding: 0; }
        .lines { margin-top: 24px; }
        .lines th, .lines td { border-bottom: 1px solid #d6d3d1; padding: 6px; text-align: left; }
        .lines .num { text-align: right; white-space: nowrap; }
        .totals { margin-top: 12px; width: 45%; margin-left: 55%; }
        .totals td { padding: 4px 6px; }
        .muted { color: #57534e; }
        .footer { margin-top: 40px; font-size: 9px; color: #57534e; }
    </style>
</head>
<body>
    {{-- Everything is escaped; remote resources are disabled in DomPDF. --}}
    <table class="meta">
        <tr>
            <td>
                <h1>{{ __('Facture') }} {{ $invoice->number }}</h1>
                <p class="muted">{{ __('Date') }} : {{ $invoice->issued_at->format('d/m/Y') }}</p>
            </td>
            <td style="text-align: right;">
                <strong>{{ $company['company_name'] }}</strong><br>
                {{ $company['address'] }}<br>
                ICE : {{ $company['ice'] }}
                @if ($company['if']) <br>IF : {{ $company['if'] }} @endif
                @if ($company['rc']) <br>RC : {{ $company['rc'] }} @endif
                @if ($company['patente']) <br>{{ __('Patente') }} : {{ $company['patente'] }} @endif
            </td>
        </tr>
    </table>

    <p style="margin-top: 24px;">
        <strong>{{ __('Client') }}</strong><br>
        {{ $invoice->client_name }}<br>
        @if ($invoice->client_address) {{ $invoice->client_address }}<br> @endif
        @if ($invoice->client_ice) ICE : {{ $invoice->client_ice }} @endif
    </p>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('Désignation') }}</th>
                <th class="num">{{ __('Qté') }}</th>
                <th class="num">{{ __('Prix unitaire HT') }}</th>
                <th class="num">{{ __('Total HT') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line['description'] }}</td>
                    <td class="num">{{ $line['quantity'] }}</td>
                    <td class="num">{{ number_format((float) $line['unit_price'], 2, ',', ' ') }} DH</td>
                    <td class="num">{{ number_format((float) $line['total'], 2, ',', ' ') }} DH</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>{{ __('Total HT') }}</td><td class="num">{{ number_format((float) $invoice->subtotal, 2, ',', ' ') }} DH</td></tr>
        <tr><td>{{ __('TVA') }} ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2, ',', ''), '0'), ',') }} %)</td><td class="num">{{ number_format((float) $invoice->tax_amount, 2, ',', ' ') }} DH</td></tr>
        <tr><td><strong>{{ __('Total TTC') }}</strong></td><td class="num"><strong>{{ number_format((float) $invoice->total, 2, ',', ' ') }} DH</strong></td></tr>
    </table>

    <p class="footer">{{ __('Facture acquittée. Paiement reçu et validé.') }}</p>
</body>
</html>
