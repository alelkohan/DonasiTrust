<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Donasi transparan yang bisa Anda lacak') &middot; DonasiTrust</title>
    <meta name="description" content="@yield('description', 'DonasiTrust melacak setiap rupiah donasi dari pembayaran sampai bukti nota. Pencairan bertahap, kuitansi terverifikasi, ledger publik.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PWA & Mobile App-like Experience Meta -->
    <meta name="theme-color" content="#069370">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="DonasiTrust">
    <link rel="manifest" href="/manifest.json">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="flex min-h-full flex-col pb-16 md:pb-0">

<a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
    Lompat ke konten utama
</a>

@include('partials.navbar')

<main id="konten" class="flex-1">
    @include('partials.flash')
    @yield('content')
</main>

@include('partials.footer')
@include('partials.mobile-bottom-nav')

@livewireScripts
</body>
</html>
