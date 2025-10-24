@extends('layouts.app')

@section('title','Login')

@section('content')
<h2>Iniciar sesión</h2>
<form method="POST" action="{{ route('login.post') }}">
  @csrf
  <div style="margin-bottom:10px;">
    <label>Email</label><br>
    <input type="email" name="email" value="{{ old('email') }}" required>
    @error('email') <div class="error">{{ $message }}</div> @enderror
  </div>
  <div style="margin-bottom:10px;">
    <label>Contraseña</label><br>
    <input type="password" name="password" required>
    @error('password') <div class="error">{{ $message }}</div> @enderror
  </div>
  <div style="margin-bottom:10px;">
    <label><input type="checkbox" name="remember"> Recordarme</label>
  </div>
  <button type="submit" class="btn">Entrar</button>
</form>
@endsection
