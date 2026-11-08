<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} Report - Coffee Corner Aura</title>
    <style>
        :root{color-scheme:light}*{box-sizing:border-box}body{margin:0;background:#f3f6f4;color:#24332b;font:13px/1.45 Arial,Helvetica,sans-serif}
        .report-shell{max-width:1180px;margin:28px auto;padding:34px;background:#fff;border:1px solid #e1e9e3;border-radius:16px;box-shadow:0 8px 30px rgba(27,55,38,.07)}
        .report-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;border-bottom:2px solid #176b4b;padding-bottom:20px;margin-bottom:18px}
        .brand{font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#176b4b;font-size:11px}.report-title{font-size:27px;line-height:1.15;margin:8px 0 5px}.muted{color:#718077}.report-meta{text-align:right;color:#718077;font-size:11px;white-space:nowrap}
        .report-summary{display:flex;gap:18px;flex-wrap:wrap;margin:0 0 18px}.summary-chip{background:#f0f6f2;border:1px solid #e0ebe3;padding:8px 12px;border-radius:9px;font-size:11px}
        .table-wrap{overflow:visible}table{width:100%;border-collapse:collapse;font-size:11px}thead{display:table-header-group}th{background:#edf4ef;color:#375044;text-align:left;text-transform:uppercase;letter-spacing:.04em;font-size:9px;padding:10px 9px;border-bottom:1px solid #d9e5dc;white-space:nowrap}td{padding:8px 9px;border-bottom:1px solid #edf1ee;vertical-align:top;overflow-wrap:anywhere}tbody tr:nth-child(even){background:#fafcfb}.empty{text-align:center;color:#7a887f;padding:28px}
        .report-foot{margin-top:18px;padding-top:12px;border-top:1px solid #e6ece8;color:#829087;font-size:10px;display:flex;justify-content:space-between;gap:10px}
        .toolbar{max-width:1180px;margin:18px auto 0;display:flex;justify-content:flex-end;gap:8px}.toolbar button{border:0;background:#176b4b;color:#fff;padding:10px 15px;border-radius:9px;font-weight:700;cursor:pointer}.toolbar a{border:1px solid #dce5df;color:#43564a;background:white;padding:9px 14px;border-radius:9px;text-decoration:none}
        @media(max-width:700px){.report-shell{margin:10px;padding:16px;border-radius:10px}.report-head{display:block}.report-meta{text-align:left;margin-top:12px}.table-wrap{overflow-x:auto}.toolbar{margin:10px;}}
        @media print{@page{size:A4 landscape;margin:12mm}body{background:#fff;font-size:10px}.toolbar{display:none!important}.report-shell{max-width:none;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}.report-head{break-after:avoid}tr{break-inside:avoid}th,td{padding:6px 5px}.report-foot{position:fixed;bottom:0;left:0;right:0;background:#fff}}
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="javascript:window.close()">Close report</a>
        <button type="button" onclick="window.print()"><span>Print / Save as PDF</span></button>
    </div>
    <main class="report-shell">
        <header class="report-head">
            <div><div class="brand">Coffee Corner Aura · Management Report</div><h1 class="report-title">{{ $title }}</h1><div class="muted">{{ $totalRows }} record(s) · {{ $filterSummary }}</div></div>
            <div class="report-meta">Generated<br><strong>{{ $generatedAt->format('d M Y, h:i A') }}</strong><br>Currency: USD unless stated</div>
        </header>
        <div class="report-summary"><div class="summary-chip"><strong>{{ number_format($totalRows) }}</strong> records included</div><div class="summary-chip">Filtered results only · Full matching dataset (not just the current page)</div></div>
        <div class="table-wrap">
            <table>
                <thead><tr>@foreach($headers as $header)<th>{{ str_replace('_', ' ', $header) }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>@foreach($row as $cell)<td>{{ $cell === null || $cell === '' ? '—' : $cell }}</td>@endforeach</tr>
                    @empty
                        <tr><td class="empty" colspan="{{ count($headers) }}">No records match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="report-foot"><span>Coffee Corner Aura · Confidential business report</span><span>Generated {{ $generatedAt->format('Y-m-d H:i:s') }}</span></footer>
    </main>
    <script>window.addEventListener('load',()=>{setTimeout(()=>window.print(),350)});</script>
</body>
</html>
