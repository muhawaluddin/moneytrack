@extends('layouts.app')
@section('title',isset($account)?'Edit Akun':'Tambah Akun') @section('heading',isset($account)?'Edit Sumber Kas':'Sumber Kas Baru')
@section('content')
<form class="card form-grid web-form" method="post" action="{{ isset($account)?route('accounts.update',$account):route('accounts.store') }}">
    @csrf @isset($account) @method('put') @endisset
    <div class="field"><label>Nama akun</label><input class="input" name="name" value="{{ old('name',$account->name??'') }}" placeholder="BCA Utama" required autofocus></div>
    <div class="field"><label>Tipe</label><select class="input" name="type" required>@foreach(['bank'=>'Bank','ewallet'=>'E-Wallet','cash'=>'Kas Tunai','credit'=>'Kartu Kredit','savings'=>'Tabungan/Deposito','other'=>'Lainnya'] as $key=>$label)<option value="{{ $key }}" @selected(old('type',$account->type??'')===$key)>{{ $label }}</option>@endforeach</select></div>
    @if($currentSpace->type==='family')<input type="hidden" name="visibility" value="shared"><div class="privacy-note span-2"><strong>Sumber kas keluarga</strong><br><span class="muted">Akun ini tersedia bagi seluruh anggota ruang {{ $currentSpace->name }}. Untuk data pribadi, pindah ke ruang pribadi terlebih dahulu.</span></div>@else<input type="hidden" name="visibility" value="personal"><div class="privacy-note span-2"><strong>Sumber kas pribadi</strong><br><span class="muted">Akun ini hanya masuk ke dashboard dan transaksi ruang pribadi Anda.</span></div>@endif
    <div class="field"><label>Nama bank / penyedia</label><input class="input" name="bank_name" value="{{ old('bank_name',$account->bank_name??'') }}"></div>
    <div class="field"><label>Nomor rekening (terenkripsi)</label><input class="input" name="account_number" value="{{ old('account_number',$account->account_number??'') }}"></div>
    <div class="field"><label>Saldo awal</label><input class="input" type="text" inputmode="numeric" data-money name="opening_balance" value="{{ old('opening_balance',isset($account)?(int)(float)$account->opening_balance:0) }}" @readonly(isset($account)) required></div>
    <div class="field"><label>Warna kartu</label><input class="input" type="color" name="color" value="{{ old('color',$account->color??'#087f70') }}"></div>
    <input type="hidden" name="currency" value="IDR"><div class="field span-2"><label>Catatan</label><textarea class="input" name="notes">{{ old('notes',$account->notes??'') }}</textarea></div>
    <div class="actions span-2"><button class="btn btn-primary">Simpan sumber kas</button><a class="btn btn-soft" href="{{ route('accounts.index') }}">Batal</a></div>
</form>
@endsection
