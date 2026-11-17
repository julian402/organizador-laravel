@extends('layouts.app')

@section('title', 'Recuperar contraseña')

@section('content')
<div class="card">
    <h1>Recuperar contraseña</h1>
    <p class="muted">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva contraseña.</p>

    {{-- CA-03.2: mismo mensaje exista o no la cuenta --}}
    @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="@error('email') is-invalid @enderror" autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn block">Enviar enlace de recuperación</button>
    </form>

    <p class="center muted"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
</div>
@endsection
