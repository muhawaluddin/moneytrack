@extends('layouts.app')
@section('title',isset($transaction)?'Edit Transaksi':'Tambah Transaksi')
@section('heading',isset($transaction)?'Edit Transaksi':'Catat Transaksi')
@section('content')
<form class="card form-grid" method="post" action="{{ isset($transaction)?route('transactions.update',$transaction):route('transactions.store') }}">
    @csrf @isset($transaction)@method('put')@endisset
    <div class="field span-2"><label>Jenis transaksi</label><select class="input" name="type" id="type" required>@foreach(['expense'=>'Pengeluaran','income'=>'Pemasukan','transfer'=>'Transfer'] as $key=>$label)<option value="{{ $key }}" @selected(old('type',$transaction->type??request('type','expense'))===$key)>{{ $label }}</option>@endforeach</select></div>
    <div class="field span-2"><label>Nominal</label><input class="input receipt-amount" type="text" inputmode="numeric" data-money name="amount" value="{{ old('amount',isset($transaction)?(int)(float)$transaction->amount:'') }}" placeholder="0" required autofocus></div>
    <div class="field"><label>Akun sumber / tujuan pemasukan</label><select class="input" name="account_id" required>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('account_id',$transaction->account_id??request('account_id'))==$account->id)>{{ $account->name }} · Rp {{ number_format($account->current_balance,0,',','.') }}</option>@endforeach</select></div>
    <div class="field transfer-field"><label>Akun tujuan transfer</label><select class="input" name="destination_account_id"><option value="">Pilih akun</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('destination_account_id',$transaction->destination_account_id??null)==$account->id)>{{ $account->name }}</option>@endforeach</select></div>
    <div class="field category-field"><label>Kategori</label><select class="input" name="category_id"><option value="">Pilih kategori</option>@foreach($categories->groupBy('type') as $type=>$items)<optgroup label="{{ $type==='income'?'Pemasukan':'Pengeluaran' }}">@foreach($items as $category)<option value="{{ $category->id }}" data-type="{{ $category->type }}" @selected(old('category_id',$transaction->category_id??null)===$category->id)>{{ $category->name }}</option>@endforeach</optgroup>@endforeach</select></div>
    <div class="field"><label>Status</label><select class="input" name="status"><option value="paid" @selected(old('status',$transaction->status??'paid')==='paid')>Lunas</option><option value="pending" @selected(old('status',$transaction->status??'paid')==='pending')>Pending</option></select></div>
    <div class="field"><label>Tanggal & waktu</label><input class="input" type="datetime-local" name="transacted_at" value="{{ old('transacted_at',isset($transaction)?$transaction->transacted_at->format('Y-m-d\TH:i'):now()->format('Y-m-d\TH:i')) }}" required></div>
    <div class="field"><label>Catatan</label><input class="input" name="description" value="{{ old('description',$transaction->description??'') }}" placeholder="Makan siang"></div>
    <div class="field"><label>Berulang</label><select class="input" name="recurring_rule"><option value="">Tidak berulang</option>@foreach(['daily'=>'Harian','weekly'=>'Mingguan','monthly'=>'Bulanan','yearly'=>'Tahunan'] as $key=>$label)<option value="{{ $key }}" @selected(old('recurring_rule',$transaction->recurring_rule??'')===$key)>{{ $label }}</option>@endforeach</select><input type="hidden" name="is_recurring" id="is_recurring" value="0"></div>
    <div class="actions span-2"><button class="btn btn-primary">Simpan transaksi</button><a class="btn btn-soft" href="{{ route('transactions.index') }}">Batal</a>@isset($transaction)<button class="btn btn-danger" type="submit" form="delete-transaction">Hapus</button>@endisset</div>
</form>
@isset($transaction)<form id="delete-transaction" method="post" action="{{ route('transactions.destroy',$transaction) }}" onsubmit="return confirm('Hapus transaksi dan koreksi saldo?')">@csrf @method('delete')</form>@endisset
<script>
const type = document.querySelector('#type');
const transfer = document.querySelector('.transfer-field');
const category = document.querySelector('.category-field');
const rule = document.querySelector('[name=recurring_rule]');
const recurring = document.querySelector('#is_recurring');

const categorySelect = category?.querySelector('select');
const originalOptgroups = categorySelect ? Array.from(categorySelect.querySelectorAll('optgroup')) : [];
const defaultOption = categorySelect ? categorySelect.querySelector('option[value=""]') : null;
let currentSelectedVal = categorySelect ? categorySelect.value : '';

if (categorySelect) {
    categorySelect.addEventListener('change', () => {
        currentSelectedVal = categorySelect.value;
    });
}

function sync() {
    transfer.style.display = type.value === 'transfer' ? 'grid' : 'none';
    category.style.display = type.value === 'transfer' ? 'none' : 'grid';
    recurring.value = rule.value ? '1' : '0';

    if (type.value !== 'transfer' && categorySelect) {
        categorySelect.innerHTML = '';
        if (defaultOption) {
            categorySelect.appendChild(defaultOption.cloneNode(true));
        }

        const targetType = type.value; // 'income' or 'expense'
        originalOptgroups.forEach(group => {
            const firstOption = group.querySelector('option');
            if (firstOption && firstOption.dataset.type === targetType) {
                categorySelect.appendChild(group.cloneNode(true));
            }
        });

        // Restore selected value if still valid, otherwise reset
        if (Array.from(categorySelect.options).some(opt => opt.value === currentSelectedVal)) {
            categorySelect.value = currentSelectedVal;
        } else {
            categorySelect.value = '';
            currentSelectedVal = '';
        }
    }
}

type.addEventListener('change', sync);
rule.addEventListener('change', sync);
sync();
</script>
@endsection
