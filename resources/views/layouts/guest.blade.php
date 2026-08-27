<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') · MOROWALI JUARA COMMAND CENTER</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-800 via-emerald-700 to-blue-800 text-white flex items-center justify-center p-4">

{{-- Decorative blurred shapes --}}
<div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-[28rem] h-[28rem] bg-blue-600/20 rounded-full blur-3xl"></div>
</div>

<div class="w-full max-w-md relative">
    @yield('content')
</div>

@stack('scripts')
</body>
</html>
