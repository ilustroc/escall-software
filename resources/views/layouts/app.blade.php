<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>@yield('title','ESCALL - Admin')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{
      --b: #e0e0e0;
      --text: #333;
      --bg: #f8f9fa;
      --link: #2c3e50;
      --active: #0b5ed7;
      --active-bg: #eef4ff;
      --shadow: 0 4px 8px rgba(0,0,0,0.08); /* Sombra un poco más pronunciada para dropdowns */
      
      --ok: #e7f6ef; --warn: #fff7e6; --err: #fdecea;
    }
    *{box-sizing:border-box}
    body{
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      margin: 0;
      padding: 20px;
      color: var(--text);
      background: var(--bg);
      line-height: 1.5;
    }
    a{color:var(--link); text-decoration:none; transition: color 0.2s ease;}
    a:hover { color: var(--active); }

    .box{
      border: 1px solid var(--b);
      padding: 16px;
      margin-bottom: 16px;
      border-radius: 8px;
      background: #fff;
      box-shadow: 0 2px 6px rgba(0,0,0,0.07);
    }
    
    /* Botones y formularios */
    .btn{
      padding: 8px 14px;
      border: 1px solid var(--b);
      color: #333;
      background: #fdfdfd;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn:hover{ background: #f4f4f4; border-color: #ccc; }
    input,button,select{padding:8px; border: 1px solid var(--b); border-radius: 6px;}

    /* NAV */
    .bar{
      display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;
    }
    .brand a{font-weight:700; color:#111; font-size: 1.1em;}
    
    .nav{display:flex; align-items:center; flex-wrap:wrap; gap: 4px;} /* Reducido el gap */

    /*
     * NUEVOS ESTILOS PARA DROPDOWN (Acordeón)
     */

    /* Contenedor base para cada item del menú (sea link simple o dropdown) */
    .nav-item {
      position: relative; /* Clave para posicionar el dropdown */
      border-radius: 6px;
      transition: background 0.2s ease;
    }

    /* El enlace principal de cada item */
    .nav-item > a {
      display: block;
      padding: 8px 12px;
      border: 1px solid transparent;
      border-radius: 6px;
    }

    /* Estilo al pasar el ratón por el item (link simple o dropdown) */
    .nav-item:hover > a {
      background: #f5f5f5;
    }
    
    /* Estilo ACTIVO para el link principal */
    .nav-item > a.active {
      color: var(--active);
      background: var(--active-bg);
      font-weight: 600;
    }

    /* El contenedor del sub-menú (oculto por defecto) */
    .dropdown-content {
      display: none;
      position: absolute;
      top: 100%; /* Se posiciona justo debajo del padre */
      left: 0;
      background: #fff;
      min-width: 180px; /* Ancho mínimo del desplegable */
      border: 1px solid var(--b);
      border-radius: 8px;
      box-shadow: var(--shadow);
      z-index: 10;
      margin-top: 4px; /* Pequeña separación */
      padding: 6px; /* Espacio interno para los links */
      overflow: hidden; /* Para que los links hereden el border-radius */
    }
    
    /* Los links dentro del desplegable */
    .dropdown-content a {
      display: block;
      padding: 8px 12px;
      font-size: 0.9em;
      border-radius: 6px;
    }

    /* Hover de los links internos */
    .dropdown-content a:hover {
      background: #f5f5f5;
    }

    /* Link interno ACTIVO */
    .dropdown-content a.active {
      background: var(--active-bg);
      color: var(--active);
      font-weight: 600;
    }

    /* LA MAGIA: Al hacer hover sobre el .nav-item, se muestra el .dropdown-content */
    .nav-item:hover .dropdown-content {
      display: block;
    }

    /* (Fin de los nuevos estilos) */


    /* Flash + errores (sin cambios) */
    .flash{display:grid; gap:8px; margin-bottom:12px}
    .flash > div { padding: 12px 14px; border-radius: 6px; border: 1px solid; }
    .flash .ok{background:var(--ok); border-color: #b7ebd0;}
    .flash .warn{background:var(--warn); border-color: #ffe58f;}
    .flash .err{background:var(--err); border-color: #f5c2c7;}
    
    .errors{
      background: var(--err);
      border: 1px solid #f5c2c7;
      border-radius: 6px;
      padding: 12px 14px;
    }
    .errors ul{margin: 8px 0 0 20px; padding: 0;}
  </style>
</head>
<body>

  <div class="box bar">
    <div class="brand"><a href="{{ route('dashboard') }}">ESCALL PERÚ — Admin</a></div>

    @auth
      <nav class="nav">
        
        <div class="nav-item">
          <a href="{{ route('dashboard') }}"
             class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Inicio</a>
        </div>

        <div class="nav-item"> <a href="{{ route('cargas.index') }}" 
             class="{{ request()->routeIs('cargas.*') ? 'active' : '' }}">
             Cargas
          </a>
          <div class="dropdown-content"> <a href="{{ route('cargas.data.form') }}"
               class="{{ request()->routeIs('cargas.data.*') ? 'active' : '' }}">Data</a>
            <a href="{{ route('cargas.gestiones.form') }}"
               class="{{ request()->routeIs('cargas.gestiones.*') ? 'active' : '' }}">Gestiones</a>
            <a href="{{ route('cargas.sp.form') }}"
               class="{{ request()->routeIs('cargas.sp.*') ? 'active' : '' }}">SP</a>
          </div>
        </div>

        <div class="nav-item">
          <a href="{{ route('tablas.index') }}"
             class="{{ request()->routeIs('tablas.*') ? 'active' : '' }}">
             Tablas
          </a>
        </div>
        
        <div class="nav-item">
          <a href="{{ route('reportes.index') }}"
             class="{{ request()->routeIs('reportes.*') ? 'active' : '' }}">
             Reportes
          </a>
          <div class="dropdown-content">
            <a href="{{ route('reportes.impulse.index') }}"
               class="{{ request()->routeIs('reportes.impulse.*') ? 'active' : '' }}">Reporte Impulse</a>
            <a href="{{ route('reportes.kp.index') }}"
               class="{{ request()->routeIs('reportes.kp.*') ? 'active' : '' }}">Reporte Kp Invest</a>
            <a href="{{ route('reportes.tec.index') }}"
               class="{{ request()->routeIs(patterns: 'reportes.tec.*') ? 'active' : '' }}">Reporte Tec Invest</a>
            <a href="{{ route('reportes.carteras.index') }}"
               class="{{ request()->routeIs('reportes.carteras.*') ? 'active' : '' }}">Reporte Carteras</a>
            </div>
        </div>

        <div class="nav-item">
          <a href="{{ route('sms.index') }}"
             class="{{ request()->routeIs('sms.index') ? 'active' : '' }}">SMS</a>
        </div>

      </nav>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn">Salir</button>
      </form>
    @endauth
  </div>

  {{-- Mensajes flash & errores (Sin cambios) --}}
  @if(session('ok') || session('warn') || session('error') || $errors->any())
    <div class="flash">
      @if(session('ok'))   <div class="ok">{{ session('ok') }}</div> @endif
      @if(session('warn')) <div class="warn">{{ session('warn') }}</div> @endif
      @if(session('error'))<div class="err">{{ session('error') }}</div> @endif
      @if($errors->any())
        <div class="errors">
          <strong>Errores:</strong>
          <ul>
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
        </div>
      @endif
    </div>
  @endif

  <div class="box">
    @yield('content')
  </div>
</body>
</html>