<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inicio') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
        <nav class="nav">
            @auth
                <a href="{{ route('dashboard') }}">Mi panel</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn link">Cerrar sesión</button>
                </form>
            @else
                <a href="{{ route('login') }}">Iniciar sesión</a>
                <a class="btn" href="{{ route('register') }}">Crear cuenta</a>
            @endauth
        </nav>
    </header>

    <main class="container">
        @yield('content')
    </main>
</body>
</html>
