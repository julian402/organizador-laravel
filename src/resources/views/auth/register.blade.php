@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
<div class="card">
    <h1>Crear cuenta</h1>

    {{-- novalidate: las validaciones las hace el servidor (CA-01.2 a CA-01.5) --}}
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="field">
            <label for="name">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   class="@error('name') is-invalid @enderror" autofocus>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="@error('email') is-invalid @enderror">
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password"
                   class="@error('password') is-invalid @enderror">
            <div class="hint">Mínimo 8 caracteres, con mayúscula, minúscula y número.</div>
            @foreach ($errors->get('password') as $msg)
                <div class="error">{{ $msg }}</div>
            @endforeach
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="@error('password_confirmation') is-invalid @enderror">
            @error('password_confirmation') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn block">Registrarme</button>
    </form>

    <p class="center muted">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
</div>
@endsection
