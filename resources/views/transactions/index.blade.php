@extends('layouts.app')
@section('title','Transaksi')
@section('heading',$currentSpace->type==='family'?'Transaksi Keluarga':'Transaksi Pribadi')
@section('content')
<div class="scope-banner"><i style="background:{{ $currentSpace->color }}"></i><span><strong>{{ $currentSpace->name }}</strong><small>Hanya transaksi dari ruang aktif ini</small></span><a href="{{ route('spaces.index') }}">Ganti ruang</a></div>
<form class="card form-grid" method="get" style="margin-bottom:18px">
    <div class="field span-2"><label>Cari</label><input class="input" name="q" value="{{ request('q') }}" placeholder="Catatan atau nominal"></div>
    <div class="field"><label>Jenis</label><select class="input" name="type"><option value="">Semua</option>@foreach(['income'=>'Pemasukan','expense'=>'Pengeluaran','transfer'=>'Transfer'] as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="field"><label>Akun</label><select class="input" name="account_id"><option value="">Semua</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(request('account_id')==$account->id)>{{ $account->name }}</option>@endforeach</select></div>
    <div class="field"><label>Dari</label><input class="input" type="date" name="from" value="{{ request('from') }}"></div>
    <div class="field"><label>Sampai</label><input class="input" type="date" name="to" value="{{ request('to') }}"></div>
    <div class="actions span-2"><button class="btn btn-primary">Terapkan filter</button><a class="btn btn-soft" href="{{ route('transactions.index') }}">Reset</a><a class="btn btn-warm" href="{{ route('transactions.create') }}">+ Tambah</a></div>
</form>
<div class="card">@forelse($transactions as $transaction)<x-transaction-row :transaction="$transaction"/>@empty<div class="empty"><h2>Belum ada transaksi di ruang ini</h2><p>Catat transaksi pertama untuk {{ $currentSpace->name }}.</p></div>@endforelse{{ $transactions->links() }}</div>
@endsection
