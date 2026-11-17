@extends('layouts.app')

@section('title', 'Nueva contraseña')

@section('content')
<div class="card">
    <h1>Nueva contraseña</h1>

    @if (! $enlaceValido && ! $errors->any())
        {{-- CA-03.4 / CA-03.6: enlace vencido o ya usado --}}
        <div class="alert error">{{ \App\Http\Controllers\Auth\ResetPasswordController::MENSAJE_ENLACE_INVALIDO }}</div>
        <a class="btn block" href="{{ route('password.request') }}">Solicitar un nuevo enlace</a>
    @else
        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                       class="@error('email') is-invalid @enderror" readonly>
                @error('email') <div class="error">{{ $message }}</div> @enderror
                @if ($errors->has('email'))
                    <div class="hint"><a href="{{ route('password.request') }}">Solicitar un nuevo enlace</a></div>
                @endif
            </div>

            <div class="field">
                <label for="password">Nueva contraseña</label>
                <input id="password" type="password" name="password" autofocus
                       class="@error('password') is-invalid @enderror">
                <div class="hint">Mínimo 8 caracteres, con mayúscula, minúscula y número.</div>
                @foreach ($errors->get('password') as $msg)
                    <div class="error">{{ $msg }}</div>
                @endforeach
            </div>

            <div class="field">
                <label for="password_confirmation">Confirmar nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="@error('password_confirmation') is-invalid @enderror">
                @error('password_confirmation') <div class="error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn block">Guardar nueva contraseña</button>
        </form>
    @endif
</div>
@endsection
