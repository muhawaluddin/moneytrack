<!doctype html>
<html lang="id" data-theme="{{ auth()->user()->theme ?? 'system' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#087f70"><meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="/app-icon-192.png"><link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="/app.css?v=8"><link rel="stylesheet" href="/sharing.css?v=8"><link rel="stylesheet" href="/dashboard.css?v=8"><link rel="stylesheet" href="/interface-polish.css?v=8">
    <title>@yield('title','MoneyTrack')</title>
</head>
<body data-space-id="{{ $currentSpace->id }}" data-space-type="{{ $currentSpace->type }}" data-sync-version="{{ (int) $currentSpace->sync_version }}">
@php($unreadCount=auth()->user()->unreadNotifications()->count())
@php($menuItems=[['dashboard','Beranda','dashboard'],['notifications.index','Notifikasi','notifications.*'],['transactions.index','Transaksi','transactions.*'],['budgets.index','Anggaran','budgets.*'],['goals.index','Target Keuangan','goals.*'],['reports.index','Laporan','reports.*'],['closings.index','Tutup Buku','closings.*'],['accounts.index','Sumber Kas','accounts.*'],['spaces.index','Ruang & Anggota','spaces.*'],['categories.index','Kategori','categories.*'],['settings.index','Pengaturan','settings.*']])
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><img src="/favicon.svg" alt=""></span> MoneyTrack</a>
        <div class="space-switcher">
            <small>RUANG AKTIF</small><strong><i style="background:{{ $currentSpace->color }}"></i>{{ $currentSpace->name }}</strong>
            <div class="space-menu">
                @foreach($userSpaces as $space)
                    <form method="post" action="{{ route('spaces.switch',$space) }}">@csrf<button @disabled($space->id===$currentSpace->id)><i style="background:{{ $space->color }}"></i>{{ $space->name }} <small>{{ $space->type==='personal'?'Pribadi':'Keluarga' }}</small></button></form>
                @endforeach
                <a href="{{ route('spaces.index') }}">+ Kelola ruang</a>
            </div>
        </div>
        <nav class="nav">
            @foreach($menuItems as $item)<a href="{{ route($item[0]) }}" @class(['active'=>request()->routeIs($item[2])])>{{ $item[1] }}@if($item[0]==='notifications.index' && $unreadCount)<span class="nav-count">{{ min(99,$unreadCount) }}</span>@endif</a>@endforeach
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <a class="mobile-app-brand" href="{{ route('dashboard') }}"><img src="/favicon.svg" alt=""><span><strong>MoneyTrack</strong><small><i style="background:{{ $currentSpace->color }}"></i>{{ $currentSpace->name }}</small></span></a>
            <div class="page-heading"><div class="eyebrow">{{ $currentSpace->type==='family'?'Keuangan keluarga':'Keuangan pribadi' }}</div><h1 class="title">@yield('heading','MoneyTrack')</h1></div>
            <div class="actions">
                <a @class(['notification-bell','has-unread'=>$unreadCount>0]) href="{{ route('notifications.index') }}" aria-label="{{ $unreadCount ? $unreadCount.' notifikasi belum dibaca' : 'Tidak ada notifikasi baru' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>@if($unreadCount)<b>{{ min(99,$unreadCount) }}</b>@endif</a>
                <form class="mobile-logout-form" method="post" action="{{ route('logout') }}">@csrf<button class="mobile-logout" aria-label="Keluar dari akun"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/></svg></button></form>
                <span class="space-pill mobile-hide">{{ ucfirst($currentRole) }}</span><span class="muted mobile-hide">{{ auth()->user()->name }}</span>
                <form class="mobile-hide" method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-soft">Keluar</button></form>
            </div>
        </header>
        @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert errors">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>

<a class="fab" href="{{ route('transactions.create') }}" aria-label="Tambah transaksi">+</a>
<nav class="bottom-nav" aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" @class(['active'=>request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/></svg><span>Beranda</span></a>
    <a href="{{ route('transactions.index') }}" @class(['active'=>request()->routeIs('transactions.*')]) @if(request()->routeIs('transactions.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4v16m0-16-3 3m3-3 3 3M17 20V4m0 16 3-3m-3 3-3-3"/></svg><span>Transaksi</span></a>
    <a href="{{ route('budgets.index') }}" @class(['active'=>request()->routeIs('budgets.*')]) @if(request()->routeIs('budgets.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.2-1.3-2-3-2s-3 .8-3 2 1.3 2 3 2 3 .8 3 2-1.3 2-3 2-3-.8-3-2"/></svg><span>Anggaran</span></a>
    <a href="{{ route('reports.index') }}" @class(['active'=>request()->routeIs('reports.*')]) @if(request()->routeIs('reports.*')) aria-current="page" @endif><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg><span>Laporan</span></a>
    <button @class(['more-menu-trigger','active'=>request()->routeIs('notifications.*','goals.*','closings.*','accounts.*','spaces.*','categories.*','settings.*')]) type="button" aria-expanded="false" aria-controls="mobile-menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="19" cy="12" r="1" fill="currentColor"/></svg><span>Menu</span></button>
</nav>

<div class="menu-overlay" data-menu-close></div>
<aside class="mobile-menu" id="mobile-menu" aria-label="Semua menu" aria-hidden="true">
    <div class="mobile-menu-handle"></div>
    <div class="mobile-menu-header"><div><small>{{ $currentSpace->name }} · {{ count($menuItems) }} menu</small><strong>Semua fitur MoneyTrack</strong></div><button type="button" data-menu-close aria-label="Tutup menu">×</button></div>
    <nav class="mobile-menu-grid">
        @foreach($menuItems as $item)<a href="{{ route($item[0]) }}" @class(['active'=>request()->routeIs($item[2])])><span>{{ $item[1] }}</span>@if($item[0]==='notifications.index' && $unreadCount)<b>{{ min(99,$unreadCount) }}</b>@endif</a>@endforeach
    </nav>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-danger mobile-menu-logout">Keluar dari akun</button></form>
</aside>

<div class="sync-toast" role="status" aria-live="polite"><span><strong>Data keluarga diperbarui</strong><small>Ada perubahan dari anggota lain.</small></span><button type="button">Muat sekarang</button></div>

<script>
const systemTheme=matchMedia('(prefers-color-scheme:dark)');function syncTheme(){const theme=document.documentElement.dataset.theme;const dark=theme==='dark'||(theme==='system'&&systemTheme.matches);document.documentElement.classList.toggle('dark',dark);document.body.classList.toggle('dark',dark)}syncTheme();systemTheme.addEventListener?.('change',syncTheme);if('serviceWorker'in navigator)navigator.serviceWorker.register('/sw.js');
const menu=document.querySelector('#mobile-menu'),menuTrigger=document.querySelector('.more-menu-trigger'),menuClose=menu?.querySelector('[data-menu-close]');function setMenu(open,{restoreFocus=true}={}){if(!menu||!menuTrigger)return;document.body.classList.toggle('menu-open',open);menu.setAttribute('aria-hidden',String(!open));menu.toggleAttribute('inert',!open);menuTrigger.setAttribute('aria-expanded',String(open));if(open){setTimeout(()=>menuClose?.focus(),220)}else if(restoreFocus&&menu.contains(document.activeElement)){menuTrigger.focus()}}menu?.toggleAttribute('inert',true);menuTrigger?.addEventListener('click',event=>{event.preventDefault();setMenu(!document.body.classList.contains('menu-open'))});document.querySelectorAll('[data-menu-close]').forEach(el=>el.addEventListener('click',()=>setMenu(false)));menu?.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>setMenu(false,{restoreFocus:false})));document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.body.classList.contains('menu-open'))setMenu(false);if(event.key==='Tab'&&document.body.classList.contains('menu-open')){const focusable=[...menu.querySelectorAll('a,button:not([disabled])')];if(!focusable.length)return;const first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus()}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}}});matchMedia('(min-width:801px)').addEventListener?.('change',event=>{if(event.matches)setMenu(false,{restoreFocus:false})});
function formatMoney(input){const negative=input.value.trim().startsWith('-');let digits=input.value.replace(/\D/g,'').replace(/^0+(?=\d)/,'');input.value=(negative?'-':'')+digits.replace(/\B(?=(\d{3})+(?!\d))/g,'.')}document.querySelectorAll('[data-money]').forEach(input=>{formatMoney(input);input.addEventListener('input',()=>formatMoney(input))});document.addEventListener('submit',event=>event.target.querySelectorAll?.('[data-money]').forEach(input=>input.value=input.value.replace(/\./g,'')));
const syncToast=document.querySelector('.sync-toast');let formDirty=false,syncRequest=false,syncVersion=Number(document.body.dataset.syncVersion||0);document.querySelectorAll('form:not([method="get"]) input,form:not([method="get"]) select,form:not([method="get"]) textarea').forEach(field=>{field.addEventListener('input',()=>formDirty=true);field.addEventListener('change',()=>formDirty=true)});syncToast?.querySelector('button').addEventListener('click',()=>location.reload());async function checkFamilySync(){if(document.body.dataset.spaceType!=='family'||syncRequest)return;syncRequest=true;try{const response=await fetch('{{ route('spaces.sync') }}',{headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)return;const state=await response.json();if(Number(state.space_id)!==Number(document.body.dataset.spaceId))return;if(Number(state.version)!==syncVersion){syncVersion=Number(state.version);if(formDirty){syncToast.classList.add('show')}else{location.reload()}}}catch(error){}finally{syncRequest=false}}setInterval(checkFamilySync,3000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)checkFamilySync()});

(function() {
    function initAnimations() {
        const numberObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    obs.unobserve(el);
                    
                    const text = el.textContent.trim();
                    const match = text.match(/^([+-]?\s*Rp\s*)([0-9.]+)(.*)$/i);
                    if (match) {
                        const prefix = match[1];
                        const rawValue = match[2].replace(/\./g, '');
                        const suffix = match[3] || '';
                        const targetValue = parseInt(rawValue, 10);
                        
                        if (isNaN(targetValue) || targetValue === 0) return;
                        
                        const duration = 1000;
                        const startTime = performance.now();
                        
                        function easeOutCubic(x) {
                            return 1 - Math.pow(1 - x, 3);
                        }
                        
                        function update(now) {
                            const elapsed = now - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            const current = Math.floor(easeOutCubic(progress) * targetValue);
                            
                            const formatted = new Intl.NumberFormat('id-ID').format(current);
                            el.textContent = prefix + formatted + suffix;
                            
                            if (progress < 1) {
                                requestAnimationFrame(update);
                            } else {
                                el.textContent = text;
                            }
                        }
                        requestAnimationFrame(update);
                    }
                }
            });
        }, { threshold: 0.1 });

        const amountElements = document.querySelectorAll('.amount, .positive, .negative, .account-card strong, .row strong, .metrics strong, .cashflow-metrics strong');
        amountElements.forEach(el => {
            if (/^[+-]?\s*Rp\s*[0-9.]+/i.test(el.textContent.trim())) {
                numberObserver.observe(el);
            }
        });

        const revealObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.05, rootMargin: '0px 0px -30px 0px' });

        const revealElements = document.querySelectorAll('.card, .account-card, .health-card, .row:not(.notification-target)');
        revealElements.forEach(el => {
            el.classList.add('reveal');
            revealObserver.observe(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAnimations);
    } else {
        initAnimations();
    }
})();
</script>
</body></html>
