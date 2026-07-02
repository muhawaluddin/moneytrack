<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan MoneyTrack</title>
<style>
@page{margin:30px 34px 42px}*{box-sizing:border-box}body{font-family:"DejaVu Sans",sans-serif;color:#17312d;font-size:10px;line-height:1.45;margin:0}.header{background:#087f70;color:#fff;border-radius:16px;padding:18px 20px;margin-bottom:16px}.header-table{width:100%;border-collapse:collapse}.logo{width:52px;height:52px;border-radius:14px}.brand{font-size:22px;font-weight:bold;letter-spacing:-.5px}.subtitle{color:#c9fff5;font-size:9px;text-transform:uppercase;letter-spacing:1.5px}.report-title{text-align:right;font-size:16px;font-weight:bold}.report-period{text-align:right;color:#c9fff5}.identity{width:100%;border-collapse:separate;border-spacing:0;margin-bottom:15px;background:#f4f8f6;border:1px solid #dce4df;border-radius:12px}.identity td{padding:10px 12px}.identity .label,.section-label{color:#087f70;font-size:8px;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase}.identity strong{font-size:11px}.metrics{width:100%;border-collapse:separate;border-spacing:8px 0;margin:0 -8px 16px}.metric{border:1px solid #dce4df;border-radius:11px;padding:11px;background:#fff}.metric-label{color:#6d7f7b;font-size:8px;font-weight:bold;text-transform:uppercase}.metric-value{font-size:16px;font-weight:bold;margin-top:4px}.positive{color:#087f70}.negative{color:#c44939}.section{margin-top:16px;page-break-inside:avoid}.section-title{font-size:14px;margin:2px 0 9px}.audit{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #dce4df;border-left:6px solid #087f70;border-radius:12px;background:#fff}.audit.warning{border-left-color:#f2a33a}.audit.critical{border-left-color:#dc5a49}.audit td{padding:13px}.score{width:82px;text-align:center;background:#edf8f5}.score strong{font-size:25px;color:#087f70}.score span{display:block;font-size:7px;letter-spacing:1px;color:#6d7f7b}.finding{margin:0 0 7px;padding:7px 9px;border-radius:7px;background:#f6f3ea}.finding:last-child{margin-bottom:0}.finding strong{display:block}.finding span{color:#6d7f7b}.finding em{display:block;color:#087f70;font-style:normal;font-size:8px;margin-top:2px}.columns{width:100%;border-collapse:separate;border-spacing:12px 0;margin-left:-12px}.columns>tbody>tr>td{width:50%;vertical-align:top;padding-left:12px}.data-table{width:100%;border-collapse:collapse}.data-table th{text-align:left;color:#6d7f7b;font-size:7px;text-transform:uppercase;border-bottom:2px solid #dce4df;padding:6px 4px}.data-table td{border-bottom:1px solid #e8eeeb;padding:7px 4px;vertical-align:top}.data-table th:last-child,.data-table td:last-child{text-align:right}.rank{display:inline-block;width:19px;height:19px;line-height:19px;text-align:center;border-radius:6px;background:#e7f6f2;color:#087f70;font-weight:bold}.muted{color:#6d7f7b;font-size:8px}.share{font-weight:bold;color:#087f70}.footer{position:fixed;left:0;right:0;bottom:-26px;border-top:1px solid #dce4df;padding-top:7px;color:#6d7f7b;font-size:8px}.footer-right{float:right}.empty{color:#6d7f7b;padding:12px 0}.page-break{page-break-before:always}
</style>
</head>
<body>
<div class="header">
    <table class="header-table"><tr><td style="width:62px"><img class="logo" src="{{ $logo }}" alt="MoneyTrack"></td><td><div class="brand">MoneyTrack</div><div class="subtitle">Laporan keuangan terpercaya</div></td><td><div class="report-title">Laporan & Audit Keuangan</div><div class="report-period">{{ $from->translatedFormat('d F Y') }} – {{ $to->translatedFormat('d F Y') }}</div></td></tr></table>
</div>

<table class="identity"><tr><td><div class="label">Nama akun</div><strong>{{ $user->name }}</strong><div class="muted">{{ $user->email }}</div></td><td><div class="label">Ruang aktif</div><strong>{{ $space->name }}</strong><div class="muted">{{ $space->type==='family'?'Ruang keluarga':'Ruang pribadi' }}</div></td><td><div class="label">Sumber kas</div><strong>{{ $selectedAccount?->name??'Semua sumber kas' }}</strong><div class="muted">Dibuat {{ now()->translatedFormat('d M Y, H:i') }}</div></td></tr></table>

@php($income=(float)($totals['income']??0)) @php($expense=(float)($totals['expense']??0)) @php($net=$income-$expense)
<table class="metrics"><tr>
    <td class="metric"><div class="metric-label">Pemasukan</div><div class="metric-value positive">Rp {{ number_format($income,0,',','.') }}</div></td>
    <td class="metric"><div class="metric-label">Pengeluaran</div><div class="metric-value negative">Rp {{ number_format($expense,0,',','.') }}</div></td>
    <td class="metric"><div class="metric-label">Cashflow bersih</div><div class="metric-value {{ $net<0?'negative':'positive' }}">Rp {{ number_format($net,0,',','.') }}</div></td>
    <td class="metric"><div class="metric-label">Rasio tabungan</div><div class="metric-value">{{ $audit['saving_rate']===null?'—':number_format($audit['saving_rate'],1,',','.').'%' }}</div></td>
</tr></table>

<div class="section"><div class="section-label">Audit pintar</div><h2 class="section-title">Kondisi keuangan: {{ $audit['label'] }}</h2>
<table class="audit {{ $audit['status'] }}"><tr><td class="score"><strong>{{ $audit['score']??'—' }}</strong><span>SKOR AUDIT</span></td><td>
    @forelse($audit['findings'] as $finding)<div class="finding"><strong>{{ $finding['title'] }}</strong><span>{{ $finding['message'] }}</span><em>Saran: {{ $finding['action'] }}</em></div>@empty<div class="empty">{{ $audit['score']===null?'Belum cukup data untuk menjalankan audit.':'Tidak ditemukan pola berisiko pada periode ini.' }}</div>@endforelse
</td></tr></table></div>

<table class="columns section"><tr><td><div class="section-label">Paling boros</div><h2 class="section-title">Kategori berdasarkan total</h2><table class="data-table"><thead><tr><th>Kategori</th><th>Total</th></tr></thead><tbody>
@forelse($categories->take(8) as $category)<tr><td><strong>{{ $category->name }}</strong><div class="muted">{{ $category->count }} transaksi · {{ number_format($category->share,1,',','.') }}%</div></td><td><span class="share">Rp {{ number_format($category->total,0,',','.') }}</span></td></tr>@empty<tr><td colspan="2" class="empty">Belum ada pengeluaran.</td></tr>@endforelse
</tbody></table></td><td><div class="section-label">Paling sering</div><h2 class="section-title">Kategori berdasarkan frekuensi</h2><table class="data-table"><thead><tr><th>Kategori</th><th>Frekuensi</th></tr></thead><tbody>
@forelse($frequentCategories->take(8) as $category)<tr><td><strong>{{ $category->name }}</strong><div class="muted">Rata-rata Rp {{ number_format($category->average,0,',','.') }}</div></td><td><span class="share">{{ $category->count }} kali</span></td></tr>@empty<tr><td colspan="2" class="empty">Belum ada pengeluaran.</td></tr>@endforelse
</tbody></table></td></tr></table>

<div class="section"><div class="section-label">Pengeluaran utama</div><h2 class="section-title">Transaksi terbesar yang perlu diperiksa</h2><table class="data-table"><thead><tr><th style="width:28px">#</th><th>Tanggal</th><th>Keterangan</th><th>Kategori / akun</th><th>Nominal</th></tr></thead><tbody>
@forelse($audit['largest'] as $transaction)<tr><td><span class="rank">{{ $loop->iteration }}</span></td><td>{{ $transaction->transacted_at->format('d/m/Y') }}</td><td><strong>{{ $transaction->description?:'Tanpa catatan' }}</strong></td><td>{{ $transaction->category?->name??'Tanpa kategori' }}<div class="muted">{{ $transaction->account->name }}</div></td><td class="negative"><strong>Rp {{ number_format($transaction->amount,0,',','.') }}</strong></td></tr>@empty<tr><td colspan="5" class="empty">Belum ada pengeluaran pada periode ini.</td></tr>@endforelse
</tbody></table></div>

<div class="footer">MoneyTrack · Laporan dibuat untuk {{ $user->name }}<span class="footer-right">{{ $space->name }} · {{ $from->format('d/m/Y') }}–{{ $to->format('d/m/Y') }}</span></div>
</body></html>
