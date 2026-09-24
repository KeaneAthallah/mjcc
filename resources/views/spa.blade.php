<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MOROWALI JUARA COMMAND CENTER</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app/main.tsx'])
</head>
<body class="bg-gray-100 text-gray-800 h-dvh overflow-hidden">
    <div id="app" class="h-full overflow-y-auto"></div>
</body>
</html>