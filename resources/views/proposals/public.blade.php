<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $proposal->title }} — Proposal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #eef0f2; color: #181a1c; padding: 32px 16px; }
        .card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; }
        .header { background: #f26a2e; color: #fff; padding: 28px 32px; }
        .header .eyebrow { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; opacity: .85; margin-bottom: 6px; }
        .header h1 { font-size: 26px; font-weight: 700; }
        .header p { margin-top: 6px; font-size: 15px; opacity: .95; }
        .body { padding: 28px 32px; }
        .banner { border-radius: 8px; padding: 14px 16px; font-weight: 600; margin-bottom: 20px; font-size: 15px; }
        .banner.accepted { background: #eaf7f1; color: #268866; }
        .banner.declined { background: #fff0f0; color: #c64f4f; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 20px; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #72777d; padding: 10px 8px; border-bottom: 1px solid #e5e7e9; }
        td { padding: 12px 8px; border-bottom: 1px solid #eef0f2; font-size: 15px; }
        td.num, th.num { text-align: right; }
        .totals { max-width: 320px; margin-left: auto; }
        .totals .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 15px; color: #72777d; }
        .totals .row.grand { font-size: 20px; font-weight: 700; color: #181a1c; border-top: 2px solid #f26a2e; margin-top: 8px; padding-top: 12px; }
        .meta { margin-top: 24px; font-size: 14px; color: #72777d; }
        .notes { margin-top: 16px; background: #f7f8f9; border-radius: 8px; padding: 14px 16px; font-size: 14px; white-space: pre-wrap; }
        .actions { display: flex; gap: 12px; margin-top: 28px; flex-wrap: wrap; }
        .btn { flex: 1; min-width: 160px; padding: 14px; border: none; border-radius: 8px; font-family: inherit; font-size: 16px; font-weight: 700; cursor: pointer; }
        .btn-accept { background: #f26a2e; color: #fff; }
        .btn-accept:hover { background: #d9551c; }
        .btn-decline { background: #fff; color: #c64f4f; border: 1px solid #e5e7e9; }
        .btn-decline:hover { background: #fff0f0; }
        .reason { width: 100%; margin-top: 16px; }
        .reason label { display: block; font-size: 13px; color: #72777d; margin-bottom: 6px; }
        .reason textarea { width: 100%; border: 1px solid #e5e7e9; border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: 14px; resize: vertical; min-height: 70px; }
        .footer { text-align: center; padding: 18px; font-size: 13px; color: #72777d; }
        @media (max-width: 560px) { .header, .body { padding-left: 20px; padding-right: 20px; } }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <div class="eyebrow">Proposal</div>
        <h1>{{ $proposal->title }}</h1>
        <p>Prepared for {{ $proposal->lead->business_name ?? 'you' }}</p>
    </div>
    <div class="body">
        @if ($proposal->status === 'accepted')
            <div class="banner accepted">This proposal has been accepted. Thank you!</div>
        @elseif ($proposal->status === 'declined')
            <div class="banner declined">This proposal has been declined.</div>
        @endif

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Price</th>
                <th class="num">Amount</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($proposal->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="num">{{ $item->qty }}</td>
                    <td class="num">PKR {{ number_format($item->price, 0) }}</td>
                    <td class="num">PKR {{ number_format($item->qty * $item->price, 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Subtotal</span><span>PKR {{ number_format($proposal->subtotal, 0) }}</span></div>
            <div class="row"><span>Discount</span><span>− PKR {{ number_format($proposal->discount, 0) }}</span></div>
            <div class="row grand"><span>Total</span><span>PKR {{ number_format($proposal->total, 0) }}</span></div>
        </div>

        @if ($proposal->valid_until)
            <div class="meta">Valid until {{ \Carbon\Carbon::parse($proposal->valid_until)->format('d M Y') }}</div>
        @endif

        @if ($proposal->notes)
            <div class="notes">{{ $proposal->notes }}</div>
        @endif

        @if (! in_array($proposal->status, ['accepted', 'declined'], true))
            <div class="actions">
                <form method="POST" action="{{ route('proposals.accept', $proposal->token) }}" style="flex:1; min-width:160px; display:flex;">
                    @csrf
                    <button type="submit" class="btn btn-accept" style="flex:1;">Accept proposal</button>
                </form>
                <form method="POST" action="{{ route('proposals.decline', $proposal->token) }}" style="flex:1; min-width:160px;" id="declineForm">
                    @csrf
                    <button type="submit" class="btn btn-decline" style="width:100%;" onclick="return confirm('Decline this proposal?')">Decline</button>
                    <div class="reason">
                        <label for="reason">Reason for declining (optional)</label>
                        <textarea name="reason" id="reason" placeholder="Tell us why, so we can do better"></textarea>
                    </div>
                </form>
            </div>
        @endif
    </div>
    <div class="footer">Powered by LeadFlow</div>
</div>
</body>
</html>
