@extends('layouts.app')
@section('title','Audit Keuangan')
@section('heading','Laporan & Audit Keuangan')
@section('content')
<div class="scope-banner"><i style="background:{{ $currentSpace->color }}"></i><span><strong>{{ $currentSpace->name }}</strong><small>{{ $from->translatedFormat('d M Y') }} – {{ $to->translatedFormat('d M Y') }}</small></span><a href="{{ route('spaces.index') }}">Ganti ruang</a></div>
<form class="card report-filter" method="get">
    <div class="field"><label>Periode</label><select class="input" name="period" id="report-period"><option value="this_month" @selected($period==='this_month')>Bulan ini</option><option value="last_month" @selected($period==='last_month')>Bulan lalu</option><option value="last_30_days" @selected($period==='last_30_days')>30 hari terakhir</option><option value="custom" @selected($period==='custom')>Rentang kustom</option></select></div>
    <div class="field custom-period"><label>Dari tanggal</label><input class="input" type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
    <div class="field custom-period"><label>Sampai tanggal</label><input class="input" type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
    <div class="field"><label>Sumber kas</label><select class="input" name="account_id"><option value="">Semua akun</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(request('account_id')==$account->id)>{{ $account->name }}</option>@endforeach</select></div>
    <div class="actions report-filter-actions"><button class="btn btn-primary">Analisis periode</button><a class="btn btn-warm" href="{{ route('reports.pdf',request()->query()) }}">Unduh PDF</a><a class="btn btn-soft" href="{{ route('reports.export',request()->query()) }}">Export CSV</a></div>
</form>

@php($income=(float)($totals['income']??0)) @php($expense=(float)($totals['expense']??0)) @php($net=$income-$expense)
<section class="grid grid-4 report-metrics">
    <div class="card"><div class="eyebrow">Pemasukan</div><div class="metric positive">Rp {{ number_format($income,0,',','.') }}</div></div>
    <div class="card"><div class="eyebrow">Pengeluaran</div><div class="metric negative">Rp {{ number_format($expense,0,',','.') }}</div></div>
    <div class="card"><div class="eyebrow">Cashflow bersih</div><div @class(['metric','positive'=>$net>=0,'negative'=>$net<0])>Rp {{ number_format($net,0,',','.') }}</div></div>
    <div class="card"><div class="eyebrow">Rasio tabungan</div><div class="metric">{{ $audit['saving_rate']===null?'—':number_format($audit['saving_rate'],1,',','.').'%' }}</div><small class="muted">{{ $audit['expense_change']===null?'Belum ada pembanding':(($audit['expense_change']>=0?'Naik ':'Turun ').number_format(abs($audit['expense_change']),0,',','.').'% dari periode lalu') }}</small></div>
</section>

<section class="audit-card audit-{{ $audit['status'] }}">
    <div class="audit-score"><span>{{ $audit['score']??'—' }}</span><small>SKOR AUDIT</small></div>
    <div class="audit-content"><div class="eyebrow">Audit pintar · {{ $audit['period_days'] }} hari</div><h2>{{ $audit['label'] }}</h2>
        @if($audit['findings']->isEmpty())<p class="muted">{{ $audit['score']===null?'Catat pemasukan dan pengeluaran agar audit dapat dijalankan.':'Tidak ditemukan pola berisiko pada periode ini.' }}</p>
        @else<div class="audit-findings">@foreach($audit['findings'] as $finding)<div class="audit-finding finding-{{ $finding['severity'] }}"><i></i><span><strong>{{ $finding['title'] }}</strong><small>{{ $finding['message'] }}</small><em>{{ $finding['action'] }}</em></span></div>@endforeach</div>@endif
    </div>
</section>

<section class="grid grid-2" style="margin-top:18px">
    <div class="card"><div class="eyebrow">Paling boros</div><h2>Kategori berdasarkan total</h2>
        @forelse($categories->take(8) as $category)<div class="category-audit-row"><i style="background:{{ $category->color }}"></i><span><strong>{{ $category->name }}</strong><small>{{ $category->count }} transaksi · rata-rata Rp {{ number_format($category->average,0,',','.') }}</small><span class="audit-progress"><b style="width:{{ min(100,$category->share) }}%;background:{{ $category->color }}"></b></span></span><div><strong>Rp {{ number_format($category->total,0,',','.') }}</strong><small>{{ number_format($category->share,1,',','.') }}%</small></div></div>@empty<div class="empty">Belum ada pengeluaran pada periode ini.</div>@endforelse
    </div>
    <div class="card"><div class="eyebrow">Paling sering</div><h2>Kategori berdasarkan frekuensi</h2>
        @forelse($frequentCategories->take(8) as $index=>$category)<div class="frequency-row"><span>{{ $index+1 }}</span><div><strong>{{ $category->name }}</strong><small>Rata-rata Rp {{ number_format($category->average,0,',','.') }} per transaksi</small></div><b>{{ $category->count }}×</b></div>@empty<div class="empty">Belum ada pengeluaran pada periode ini.</div>@endforelse
    </div>
</section>

<section class="grid grid-2" style="margin-top:18px">
    <div class="card scroll"><div class="eyebrow">Kronologi</div><h2>Aktivitas harian</h2><table><thead><tr><th>Tanggal</th><th>Masuk</th><th>Keluar</th></tr></thead><tbody>@forelse($daily as $date=>$amounts)<tr><td>{{ \Carbon\Carbon::parse($date)->translatedFormat('d M') }}</td><td class="positive">Rp {{ number_format($amounts['income'],0,',','.') }}</td><td class="negative">Rp {{ number_format($amounts['expense'],0,',','.') }}</td></tr>@empty<tr><td colspan="3">Belum ada aktivitas.</td></tr>@endforelse</tbody></table></div>
    <div class="card"><div class="eyebrow">Perlu diperiksa</div><h2>Pengeluaran terbesar</h2>@forelse($audit['largest'] as $transaction)<div class="row"><div class="icon">{{ $loop->iteration }}</div><div><strong>{{ $transaction->description?:($transaction->category?->name??'Tanpa kategori') }}</strong><small class="muted">{{ $transaction->transacted_at->translatedFormat('d M Y') }} · {{ $transaction->account->name }}</small></div><strong class="negative">Rp {{ number_format($transaction->amount,0,',','.') }}</strong></div>@empty<div class="empty">Belum ada pengeluaran.</div>@endforelse</div>
</section>
<script>const period=document.querySelector('#report-period'),customFields=document.querySelectorAll('.custom-period');function syncPeriod(){customFields.forEach(field=>field.style.display=period.value==='custom'?'grid':'none')}period.addEventListener('change',syncPeriod);syncPeriod()</script>
@endsection
