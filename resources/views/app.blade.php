<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style layout-navbar-fixed layout-menu-fixed">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title inertia>{{ config('app.name', 'RoomRise') }}</title>

    {{-- <link rel="shortcut icon" href="{{ asset('img/favicon/favicon.ico') }}"> --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

    @routes
    @vite(['resources/js/app.js', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body>
    @inertia

    <script>
        const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#7367F0'
        const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#7367F0'
        if (loaderColor)
            document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
        if (loaderColor)
            document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
        if (primaryColor)
            document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
    </script>
</body>

</html>
