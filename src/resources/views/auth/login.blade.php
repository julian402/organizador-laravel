@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<div class="card">
    <h1>Iniciar sesión</h1>

    @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="@error('email') is-invalid @enderror" autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password"
                   class="@error('password') is-invalid @enderror">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn block">Entrar</button>
    </form>

    <p class="center muted">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
</div>
@endsection
