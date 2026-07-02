@extends('layouts.app')
@section('title','Kelola Ruang')
@section('heading','Ruang & Anggota')
@section('content')
@if(session('invitation_url'))
    <div class="card" style="margin-bottom:18px"><div class="eyebrow">Tautan undangan siap</div><p>Bagikan tautan ini secara pribadi. Tautan berlaku selama 7 hari.</p><div class="invite-link">{{ session('invitation_url') }}</div></div>
@endif

<section class="card" style="margin-bottom:18px">
    <div class="actions" style="justify-content:space-between"><div><div class="eyebrow">Ruang Anda</div><h2>Pilih ruang aktif</h2></div><span class="badge">{{ $userSpaces->count() }} ruang</span></div>
    <div class="space-grid">
        @foreach($userSpaces as $item)
            <form method="post" action="{{ route('spaces.switch',$item) }}">@csrf
                <button @class(['space-tile','active'=>$item->id===$space->id]) @disabled($item->id===$space->id)>
                    <i style="background:{{ $item->color }}"></i><span><strong>{{ $item->name }}</strong><small>{{ $item->type==='personal'?'Pribadi':'Keluarga' }}</small></span>@if($item->id===$space->id)<b>Aktif</b>@endif
                </button>
            </form>
        @endforeach
    </div>
</section>

<div class="grid grid-2">
    <section class="card">
        <div class="eyebrow">Ruang aktif</div><h2>{{ $space->name }}</h2>
        <p class="muted">{{ $space->type==='personal'?'Ruang pribadi hanya dapat dilihat oleh Anda.':'Data bersama tersedia sesuai tingkat akses setiap akun.' }}</p>
        <div class="privacy-note"><strong>Privasi tetap terjaga</strong><br><span class="muted">Akun berlabel pribadi tidak terlihat oleh anggota lain.</span></div>
    </section>

    @if($space->canManage(auth()->user()))
        <form class="card form-grid" method="post" action="{{ route('spaces.update',$space) }}">@csrf @method('put')
            <div class="span-2"><div class="eyebrow">Identitas ruang</div><h2>Edit ruang</h2></div>
            <div class="field"><label>Nama ruang</label><input class="input" name="name" value="{{ old('name',$space->name) }}" required></div>
            <div class="field"><label>Warna ruang</label><input class="input" type="color" name="color" value="{{ old('color',$space->color) }}"></div>
            <button class="btn btn-primary span-2">Simpan perubahan</button>
        </form>
    @endif

    @if($space->type==='personal')
        <form class="card form-grid" method="post" action="{{ route('spaces.store') }}">@csrf
            <div class="span-2"><div class="eyebrow">Mulai berbagi</div><h2>Buat ruang keluarga</h2></div>
            <div class="field"><label>Nama ruang</label><input class="input" name="name" placeholder="Keluarga Wall" required></div>
            <div class="field"><label>Warna</label><input class="input" type="color" name="color" value="#e58b27"></div>
            <button class="btn btn-primary span-2">Buat ruang keluarga</button>
        </form>
    @elseif($space->canManage(auth()->user()))
        <form class="card form-grid" method="post" action="{{ route('invitations.store') }}">@csrf
            <div class="span-2"><div class="eyebrow">Anggota baru</div><h2>Kirim undangan</h2></div>
            <div class="field"><label>Email</label><input class="input" type="email" name="email" placeholder="anggota@email.com" required></div>
            <div class="field"><label>Peran</label><select class="input" name="role"><option value="manager">Pengelola</option><option value="contributor">Pencatat</option></select></div>
            <button class="btn btn-primary span-2">Buat tautan undangan</button>
        </form>
    @endif
</div>

@if($space->type==='family')
    <section class="card" style="margin-top:18px">
        <div class="actions" style="justify-content:space-between"><div><div class="eyebrow">Hak akses</div><h2>Kelola anggota</h2></div><span class="badge">{{ $space->members->count() }} anggota</span></div>
        <div class="member-list">
            @foreach($space->members as $member)
                <div class="member-manage-row">
                    <div class="avatar">{{ strtoupper(substr($member->name,0,1)) }}</div>
                    <div class="member-identity"><strong>{{ $member->name }}</strong><small>{{ $member->email }}</small></div>
                    @if($space->owner_id===auth()->id() && $member->id!==auth()->id())
                        <form class="member-role-form" method="post" action="{{ route('spaces.members.update',[$space,$member->id]) }}">@csrf @method('put')<select class="input" name="role" aria-label="Peran {{ $member->name }}"><option value="manager" @selected($member->pivot->role==='manager')>Pengelola</option><option value="contributor" @selected($member->pivot->role==='contributor')>Pencatat</option></select><button class="btn btn-soft">Simpan</button></form>
                        <form method="post" action="{{ route('spaces.members.destroy',[$space,$member->id]) }}" onsubmit="return confirm('Keluarkan {{ addslashes($member->name) }} dari ruang ini?')">@csrf @method('delete')<button class="btn btn-danger">Keluarkan</button></form>
                    @else
                        <span class="badge">{{ $member->pivot->role==='owner'?'Pemilik':($member->pivot->role==='manager'?'Pengelola':'Pencatat') }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        @if($space->invitations->isNotEmpty())
            <h3>Undangan menunggu</h3>
            @foreach($space->invitations as $invite)
                <div class="member-manage-row"><div class="icon">@</div><div class="member-identity"><strong>{{ $invite->email }}</strong><small>{{ ucfirst($invite->role) }} · berlaku sampai {{ $invite->expires_at->translatedFormat('d M Y') }}</small></div><span class="badge">Menunggu</span>@if($space->canManage(auth()->user()))<form method="post" action="{{ route('invitations.destroy',$invite) }}">@csrf @method('delete')<button class="btn btn-danger">Batalkan</button></form>@endif</div>
            @endforeach
        @endif
    </section>

    @if($space->owner_id===auth()->id())
        <section class="card danger-zone" style="margin-top:18px"><div><div class="eyebrow">Zona berbahaya</div><h2>Hapus ruang keluarga</h2><p class="muted">Seluruh akun, transaksi, dan anggaran di ruang ini akan dihapus permanen.</p></div><form method="post" action="{{ route('spaces.destroy',$space) }}" onsubmit="return confirm('Tindakan ini tidak dapat dibatalkan. Lanjutkan?')">@csrf @method('delete')<div class="field"><label>Ketik “{{ $space->name }}” untuk konfirmasi</label><input class="input" name="space_name" required autocomplete="off"></div><button class="btn btn-danger">Hapus ruang permanen</button></form></section>
    @endif
@endif
@endsection
