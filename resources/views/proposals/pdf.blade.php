@php
    use App\Models\ProposalItem;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Number;

    /** @var \App\Models\Proposal $proposal */
    /** @var Collection<int, ProposalItem> $items */
    $items = $proposal->items;
    $totalCents = (int) $items->sum(
        static fn (ProposalItem $item): int => $item->price * $item->quantity,
    );
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('ui.proposals.pdf.proposal') }} {{ $proposal->uuid }}</title>
    <style>
        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
        }

        .page {
            padding: 42px 48px;
        }

        .header {
            border-bottom: 2px solid #172033;
            margin-bottom: 28px;
            padding-bottom: 18px;
        }

        .document-label {
            color: #64748b;
            font-size: 11px;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 6px 0 0;
        }

        .meta {
            color: #475569;
            margin-top: 8px;
        }

        .parties {
            margin-bottom: 28px;
            width: 100%;
        }

        .parties td {
            vertical-align: top;
            width: 50%;
        }

        .section-title {
            color: #64748b;
            font-size: 10px;
            letter-spacing: 1px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .muted {
            color: #64748b;
        }

        table.items {
            border-collapse: collapse;
            width: 100%;
        }

        table.items th {
            background: #f1f5f9;
            color: #334155;
            font-size: 10px;
            letter-spacing: .7px;
            padding: 10px 8px;
            text-align: left;
            text-transform: uppercase;
        }

        table.items td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .total {
            margin-left: auto;
            margin-top: 24px;
            width: 260px;
        }

        .total td {
            border-top: 2px solid #172033;
            font-size: 14px;
            font-weight: 700;
            padding-top: 12px;
        }

        .footer {
            bottom: 28px;
            color: #94a3b8;
            font-size: 10px;
            left: 48px;
            position: fixed;
            right: 48px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="document-label">{{ __('ui.proposals.pdf.document') }}</div>
            <h1>{{ $proposal->title }}</h1>
            <div class="meta">
                {{ __('ui.proposals.pdf.proposal') }} {{ $proposal->uuid }}
                @if ($proposal->valid_until !== null)
                    &middot; {{ __('ui.proposals.pdf.valid_until') }} {{ $proposal->valid_until->format('M j, Y') }}
                @endif
            </div>
        </div>

        <table class="parties">
            <tr>
                <td>
                    <div class="section-title">{{ __('ui.proposals.pdf.prepared_by') }}</div>
                    <div class="name">{{ $proposal->tenant->name }}</div>
                </td>
                <td>
                    <div class="section-title">{{ __('ui.proposals.pdf.prepared_for') }}</div>
                    <div class="name">{{ $proposal->client->name }}</div>
                    <div>{{ __('ui.proposals.pdf.email') }}: {{ $proposal->client->email }}</div>
                    @if ($proposal->client->tax_id !== null)
                        <div>{{ __('ui.proposals.pdf.tax_id') }}: {{ $proposal->client->tax_id }}</div>
                    @endif
                    @if ($proposal->client->address !== null)
                        <div class="muted">{{ $proposal->client->address }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('ui.proposals.pdf.item') }}</th>
                    <th>{{ __('ui.proposals.pdf.type') }}</th>
                    <th class="right">{{ __('ui.proposals.pdf.price') }}</th>
                    <th class="right">{{ __('ui.proposals.pdf.quantity') }}</th>
                    <th class="right">{{ __('ui.proposals.pdf.subtotal') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $lineTotalCents = $item->price * $item->quantity;
                    @endphp
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ __("ui.services.types.{$item->type->value}") }}</td>
                        <td class="right">{{ Number::currency($item->price / 100, 'USD', app()->getLocale()) }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">{{ Number::currency($lineTotalCents / 100, 'USD', app()->getLocale()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td>{{ __('ui.proposals.pdf.grand_total') }}</td>
                <td class="right">{{ Number::currency($totalCents / 100, 'USD', app()->getLocale()) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ __('ui.proposals.pdf.proposal') }} {{ $proposal->uuid }}
    </div>
</body>
</html>
