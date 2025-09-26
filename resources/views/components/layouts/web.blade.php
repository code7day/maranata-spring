<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth scrollbar-thumb-ea-blue-300 !scrollbar-track-transparent scrollbar-thin scrollbar-thumb-rounded-full scrollbar-track-rounded-full">
    <head>
        {{-- @if (env('APP_ENV','local')=='production' && ($isProductionServer??false))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gtm??"GTM-" }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gtm??"GTM-" }}', {
                'anonymize_ip': true,
                'cookie_flags': 'SameSite=None;Secure'
            });
        </script>
        <!-- Google Tag Manager -->
        <script defer>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm??"GTM-" }}');</script>
        <!-- End Google Tag Manager -->
        @endif --}}

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Maranata Spring 2025' }}</title>
        <meta name="description" content="{{ $seodescription??'' }}" />
        <meta name="keywords" content="{{ $seokeywords??'' }}" />
        {{ $og ?? '' }}
        {{-- @if (env('APP_ENV','local')=='production' && ($isProductionServer??false)) --}}
        <link rel="canonical" href="{{ url()->current() }}" />
        <base href="{{ url('/') }}" />
        <link rel="icon" href="/favicon.ico" />
        <link rel="preconnect" href="https://cdn.jsdelivr.net">
        <link rel="preconnect" href="https://www.google.com">
        <link rel="preconnect" href="https://www.googletagmanager.com">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        {{-- <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> --}}

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>


        @livewireStyles
        {{-- @filamentStyles --}}
        {{-- @fluxAppearance --}}

        <!-- Scripts -->
        @vite('resources/css/web.css')
        <!-- Styles -->

        @stack('styles')

    </head>
    <body class="antialiased min-h-screen  bg-fixed bg-gradient-to-br from-blue-100 via-cyan-50 to-green-100 flex flex-col" >

        {{ $slot }}

        @stack('modals')

        @livewireScripts
        {{-- @fluxScripts --}}
        {{-- @filamentScripts --}}
        @vite('resources/js/web.js')
        @stack('scripts')
    </body>
</html>
