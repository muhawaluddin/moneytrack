@extends('layouts.app')
@section('title','Kategori')
@section('heading','Kategori Transaksi')
@section('content')
<div class="grid grid-2">
    @if($canManage)
        <form class="card form-grid" method="post" action="{{ route('categories.store') }}">@csrf
            <div class="field"><label>Nama kategori</label><input class="input" name="name" required></div>
            <div class="field"><label>Jenis</label><select class="input" name="type"><option value="expense">Pengeluaran</option><option value="income">Pemasukan</option></select></div>
            <div class="field"><label>Warna</label><input class="input" type="color" name="color" value="#087f70"></div>
            <div class="field"><label>Sub-kategori dari</label><select class="input" name="parent_id"><option value="">Kategori utama</option>@foreach($categories->whereNull('parent_id') as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
            <button class="btn btn-primary span-2">Tambah kategori</button>
        </form>
    @endif
    <div class="card"><div class="eyebrow">{{ $currentSpace->type==='family'?'Kategori bersama':'Fleksibel' }}</div><h2>Atur sesuai kebiasaan</h2><p class="muted">{{ $currentSpace->type==='family'?'Kategori ini tersedia bagi seluruh anggota ruang keluarga.':'Kategori nonaktif tetap mempertahankan histori transaksi.' }}</p>@if(!$canManage)<span class="badge">Hanya pemilik atau pengelola yang dapat mengubah</span>@endif</div>
</div>
<div class="card" style="margin-top:18px"><div class="grid grid-2">
    @foreach($categories->groupBy('type') as $type=>$items)<div><h2>{{ $type==='income'?'Pemasukan':'Pengeluaran' }}</h2>
        @foreach($items as $category)
            @if($canManage)<form class="row" method="post" action="{{ route('categories.update',$category) }}">@csrf @method('put')<i style="width:12px;height:12px;border-radius:50%;background:{{ $category->color }}"></i><input class="input" name="name" value="{{ $category->name }}"><div class="actions"><input type="color" name="color" value="{{ $category->color }}"><input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif</label><button class="btn btn-soft">Simpan</button></div></form>
            @else<div class="row"><i style="width:12px;height:12px;border-radius:50%;background:{{ $category->color }}"></i><strong>{{ $category->name }}</strong><span class="badge">{{ $category->is_active?'Aktif':'Nonaktif' }}</span></div>@endif
        @endforeach
    </div>@endforeach
</div></div>
@endsection
