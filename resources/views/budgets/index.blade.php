@extends('layouts.app')
@section('title','Anggaran')
@section('heading','Anggaran Bulanan')
@section('content')
<div class="grid grid-2">
    <form class="card form-grid" method="post" action="{{ route('budgets.store') }}">@csrf
        <div class="field span-2"><label>Kategori pengeluaran</label><select class="input" name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
        <div class="field"><label>Bulan</label><input class="input" type="month" name="month" value="{{ $month->format('Y-m') }}" required></div>
        <div class="field"><label>Limit anggaran</label><input class="input" type="text" inputmode="numeric" data-money name="limit_amount" placeholder="1.000.000" required></div>
        <button class="btn btn-primary span-2">Simpan anggaran</button>
    </form>
    <div class="card"><div class="eyebrow">Aksi cepat</div><h2>{{ $month->translatedFormat('F Y') }}</h2><form method="get" class="actions"><input class="input" style="width:auto" type="month" name="month" value="{{ $month->format('Y-m') }}"><button class="btn btn-soft">Buka bulan</button></form><form method="post" action="{{ route('budgets.copy') }}" style="margin-top:10px">@csrf<input type="hidden" name="month" value="{{ $month->format('Y-m-d') }}"><button class="btn btn-warm">Salin anggaran bulan lalu</button></form></div>
</div>
<div class="card" style="margin-top:18px"><h2>Penggunaan anggaran</h2><div class="grid grid-2">
    @forelse($budgets as $budget)@php($used=$spent[$budget->category_id]??0)@php($pct=min(100,round($used/$budget->limit_amount*100)))
        <div class="card"><div class="actions" style="justify-content:space-between"><strong>{{ $budget->category->name }}</strong><span class="badge">{{ $pct }}%</span></div><div class="progress" style="margin:14px 0"><i style="width:{{ $pct }}%;background:{{ $pct>=100?'var(--danger)':($pct>=70?'var(--warm)':'var(--brand)') }}"></i></div><div class="muted">Rp {{ number_format($used,0,',','.') }} dari Rp {{ number_format($budget->limit_amount,0,',','.') }}</div><form method="post" action="{{ route('budgets.destroy',$budget) }}" style="margin-top:10px">@csrf @method('delete')<button class="btn btn-danger">Hapus</button></form></div>
    @empty<div class="empty">Belum ada anggaran untuk bulan ini.</div>@endforelse
</div></div>
@endsection
