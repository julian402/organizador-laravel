@extends('layouts.app')

@section('title', 'Mi panel')

@section('content')
<div class="card wide">
    <h1>Hola, {{ $user->name }}</h1>
    <p class="muted">Esta es tu área privada del organizador. Aquí aparecerán tus tareas y actividades en los próximos sprints.</p>
</div>
@endsection
