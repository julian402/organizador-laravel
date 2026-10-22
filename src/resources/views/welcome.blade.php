@extends('layouts.app')

@section('title', 'Bienvenido')

@section('content')
<div class="card wide center">
    <h1>{{ config('app.name') }}</h1>
    <p class="muted">Organiza tus tareas y actividades en un solo lugar.</p>
    <p>
        <a class="btn" href="{{ route('register') }}">Crear cuenta</a>
        &nbsp;
        <a href="{{ route('login') }}">Ya tengo cuenta</a>
    </p>
</div>
@endsection
