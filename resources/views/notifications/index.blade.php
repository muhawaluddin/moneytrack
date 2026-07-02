@extends('layouts.app')
@section('title','Notifikasi')
@section('heading','Notifikasi')
@section('content')
<section class="card notifications-card">
    <div class="actions notifications-heading">
        <div><div class="eyebrow">Pusat aktivitas</div><h2>Informasi terbaru</h2></div>
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="post" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-soft">Tandai semua dibaca</button></form>
        @endif
    </div>
    <div class="notification-list">
        @forelse($notifications as $notification)
            @php($severity=$notification->data['severity']??'info')
            <form method="post" action="{{ route('notifications.read',$notification) }}">@csrf
                <button class="notification-item {{ $notification->read_at ? '' : 'unread' }} severity-{{ $severity }}">
                    <span class="notification-icon">{{ ($notification->data['kind']??'')==='invitation'?'✉':(in_array(($notification->data['kind']??''),['transaction','financial_data'])?'↕':'!') }}</span>
                    <span><strong>{{ $notification->data['title']??'Notifikasi' }}</strong><small>{{ $notification->data['message']??'' }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span>
                    <span class="notification-action">@if(!$notification->read_at)<i class="unread-dot"></i>@endif<b aria-hidden="true">›</b></span>
                </button>
            </form>
        @empty
            <div class="empty">Belum ada notifikasi.</div>
        @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
