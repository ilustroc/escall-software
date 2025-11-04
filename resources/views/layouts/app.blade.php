<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>@yield('title','ESCALL • Administración')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <style>
    :root{
      /* === Tokens === */
      --brand: #cc3024;           /* Escall rojo corporativo */
      --brand-600:#b6281d;
      --brand-50:#fff1ef;

      --text: #1f2328;
      --muted:#6b7280;
      --bg: #f6f7fb;
      --card:#ffffff;
      --line:#e5e7eb;
      --shadow: 0 6px 18px rgba(0,0,0,.06);

      --link:#2c3e50;
      --active:#0b5ed7;
      --active-bg:#eef4ff;

      --ok:#e7f6ef;   --ok-b:#b7ebd0;
      --warn:#fff7e6; --warn-b:#ffe58f;
      --err:#fdecea;  --err-b:#f5c2c7;

      --radius:12px;
      --radius-sm:8px;
      --pad:16px;
      --pad-sm:10px;

      --container: min(1120px, 92vw);
    }
    @media (prefers-color-scheme: dark){
      :root{
        --text:#e6e6e6; --muted:#9aa0a6;
        --bg:#0f1115; --card:#151922; --line:#222732;
        --link:#91b4ff; --active:#7aa2ff; --active-bg:#13203d;
        --shadow: 0 12px 28px rgba(0,0,0,.45);
        --brand-50:#2a0f0d;
      }
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; color:var(--text); background:var(--bg);
      font: 14.5px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Inter,"Helvetica Neue",Arial,sans-serif;
    }
    a{color:var(--link); text-decoration:none; transition:.18s color}
    a:hover{color:var(--active)}
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}

    /* ====== Layout ====== */
    .wrap{min-height:100%; display:flex; flex-direction:column}
    .container{width:var(--container); margin-inline:auto; padding-inline:12px}

    /* Header sticky */
    .header{
      position:sticky; top:0; z-index:50; backdrop-filter:saturate(180%) blur(6px);
      background: color-mix(in srgb, var(--card) 92%, transparent);
      border-bottom:1px solid var(--line);
    }
    .bar{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:10px 0;
    }
    .brand{display:flex; align-items:center; gap:10px}
    .brand-logo{
      display:inline-grid; place-items:center; width:36px; height:36px; border-radius:10px;
      background:linear-gradient(145deg, var(--brand), var(--brand-600));
      color:#fff; font-weight:700;
      box-shadow: var(--shadow);
    }
    .brand a.title{color:var(--text); font-weight:700; letter-spacing:.2px}

    /* ====== Nav ====== */
    nav[role="navigation"]{display:flex; align-items:center; gap:8px; flex-wrap:wrap}
    .nav-item{position:relative}
    .nav-link{
      display:inline-flex; align-items:center; gap:8px;
      padding:8px 12px; border-radius:10px; border:1px solid transparent;
      color:var(--text)
    }
    .nav-link:hover{background:var(--brand-50)}
    .nav-link.active{background:var(--active-bg); color:var(--active); font-weight:600}

    /* Caret */
    .nav-link .caret{display:inline-block; border:4px solid transparent; border-top-color:currentColor; transform:translateY(1px)}

    /* Dropdown (hover + teclado) */
    .dropdown{position:relative}
    .menu{
      position:absolute; inset:auto auto auto 0; top:calc(100% + 8px);
      display:none; min-width:220px; background:var(--card); border:1px solid var(--line);
      border-radius:12px; box-shadow:var(--shadow); padding:6px; z-index:60;
    }
    .dropdown:focus-within .menu,
    .dropdown:hover .menu{display:block}
    .menu a{
      display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; color:var(--text)
    }
    .menu a:hover{background:color-mix(in srgb, var(--active-bg) 70%, transparent)}
    .menu a.active{background:var(--active-bg); color:var(--active); font-weight:600}

    /* User actions */
    .actions{display:flex; align-items:center; gap:8px}
    .btn{
      padding:8px 12px; border-radius:10px; border:1px solid var(--line); background:var(--card); color:var(--text);
      cursor:pointer; transition:.18s;
    }
    .btn:hover{transform:translateY(-1px); box-shadow:var(--shadow)}
    .btn-brand{background:var(--brand); border-color:transparent; color:#fff}
    .btn-brand:hover{background:var(--brand-600)}
    .btn-ghost{background:transparent}

    /* ====== Flash / errores ====== */
    .flash{display:grid; gap:10px; margin:14px 0}
    .flash > div{padding:10px 12px; border-radius:10px; border:1px solid}
    .ok{background:var(--ok); border-color:var(--ok-b)}
    .warn{background:var(--warn); border-color:var(--warn-b)}
    .err{background:var(--err); border-color:var(--err-b)}
    .errors{background:var(--err); border:1px solid var(--err-b); border-radius:10px; padding:10px 12px}
    .errors ul{margin:8px 0 0 18px; padding:0}

    /* ====== Content card ====== */
    .card{
      background:var(--card); border:1px solid var(--line); border-radius:var(--radius);
      box-shadow:var(--shadow); padding:18px; margin:16px 0;
    }

    /* ====== Breadcrumb ====== */
    .crumbs{
      display:flex; gap:8px; align-items:center; color:var(--muted); font-size:.92rem;
      padding:8px 0 0;
    }
    .crumbs a{color:inherit}
    .crumbs .sep{opacity:.55}

    /* ====== Footer ====== */
    .footer{margin-top:auto; padding:18px 0; color:var(--muted); font-size:.92rem}

    /* ====== Modo compacto (tu preferencia) ====== */
    .admin-compact .card{padding:14px}
    .admin-compact .btn{padding:6px 10px}
    .admin-compact .nav-link{padding:6px 10px}

    /* ====== Responsivo ====== */
    .hamb{display:none}
    @media (max-width: 840px){
      nav[role="navigation"]{display:none}
      .hamb{display:inline-flex}
      .nav-open nav[role="navigation"]{
        display:flex; flex-direction:column; align-items:flex-start; width:100%;
        border-top:1px solid var(--line); padding-top:8px; margin-top:8px;
      }
      .menu{position:relative; top:auto; inset:auto; box-shadow:none; border:0; padding:0; display:block}
      .menu a{padding:8px 12px}
    }
  </style>
</head>
<body class="wrap admin-compact">
  <header class="header">
    <div class="container bar">
      <div class="brand">
        <span class="brand-logo">E</span>
        <a href="{{ route('dashboard') }}" class="title">ESCALL PERÚ — Admin</a>
      </div>

      <button class="btn hamb btn-ghost" aria-expanded="false" aria-controls="mainnav" id="btnHamb">☰ <span class="sr-only">Abrir menú</span></button>

      @auth
      <nav id="mainnav" role="navigation" aria-label="Principal">
        <div class="nav-item">
          <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Inicio</a>
        </div>

        <div class="nav-item dropdown">
          <a class="nav-link {{ request()->routeIs('cargas.*') ? 'active' : '' }}" href="{{ route('cargas.index') }}" aria-haspopup="true" aria-expanded="false">
            Cargas <span class="caret" aria-hidden="true"></span>
          </a>
          <div class="menu" role="menu">
            <a href="{{ route('cargas.data.form') }}" class="{{ request()->routeIs('cargas.data.*') ? 'active' : '' }}" role="menuitem">Data</a>
            <a href="{{ route('cargas.gestiones.form') }}" class="{{ request()->routeIs('cargas.gestiones.*') ? 'active' : '' }}" role="menuitem">Gestiones</a>
            <a href="{{ route('cargas.sp.form') }}" class="{{ request()->routeIs('cargas.sp.*') ? 'active' : '' }}" role="menuitem">SP</a>
          </div>
        </div>

        <div class="nav-item">
          <a class="nav-link {{ request()->routeIs('tablas.*') ? 'active' : '' }}" href="{{ route('tablas.index') }}">Tablas</a>
        </div>

        <div class="nav-item dropdown">
          <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}" aria-haspopup="true" aria-expanded="false">
            Reportes <span class="caret" aria-hidden="true"></span>
          </a>
          <div class="menu" role="menu">
            <a href="{{ route('reportes.impulse.index') }}" class="{{ request()->routeIs('reportes.impulse.*') ? 'active' : '' }}" role="menuitem">Reporte Impulse</a>
            <a href="{{ route('reportes.kp.index') }}" class="{{ request()->routeIs('reportes.kp.*') ? 'active' : '' }}" role="menuitem">Reporte KP Invest</a>
            <a href="{{ route('reportes.tec.index') }}" class="{{ request()->routeIs('reportes.tec.*') ? 'active' : '' }}" role="menuitem">Reporte Tec Invest</a>
            <a href="{{ route('reportes.carteras.index') }}" class="{{ request()->routeIs('reportes.carteras.*') ? 'active' : '' }}" role="menuitem">Reporte Carteras</a>
          </div>
        </div>

        <div class="nav-item">
          <a class="nav-link {{ request()->routeIs('sms.index') ? 'active' : '' }}" href="{{ route('sms.index') }}">SMS</a>
        </div>
      </nav>

      <div class="actions">
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button type="submit" class="btn">Salir</button>
        </form>
      </div>
      @endauth
    </div>
  </header>

  <main class="container">
    {{-- Breadcrumb opcional --}}
    @hasSection('crumb')
      <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('dashboard') }}">Inicio</a>
        <span class="sep">/</span>
        <span>@yield('crumb')</span>
      </nav>
    @endif

    {{-- Flash & errores --}}
    @if(session('ok') || session('warn') || session('error') || $errors->any())
      <div class="flash">
        @if(session('ok'))   <div class="ok">{{ session('ok') }}</div> @endif
        @if(session('warn')) <div class="warn">{{ session('warn') }}</div> @endif
        @if(session('error'))<div class="err">{{ session('error') }}</div> @endif
        @if($errors->any())
          <div class="errors">
            <strong>Errores:</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        @endif
      </div>
    @endif

    <section class="card">@yield('content')</section>
  </main>

  <footer class="footer">
    <div class="container">
      © {{ date('Y') }} Escall Perú — <span style="color:var(--muted)">Panel de Administración</span>
    </div>
  </footer>

  <script>
    // Toggle mobile
    const btn = document.getElementById('btnHamb');
    if (btn){
      btn.addEventListener('click', () => {
        const root = document.body;
        const open = root.classList.toggle('nav-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
    // Cerrar dropdown al click fuera (solo en desktop)
    document.addEventListener('click', (e)=>{
      const isDrop = e.target.closest('.dropdown');
      if(!isDrop){
        document.querySelectorAll('.dropdown .menu').forEach(m => {
          if (getComputedStyle(m).position === 'absolute') m.style.display = 'none';
        });
        // restaurar en hover
        setTimeout(()=>document.querySelectorAll('.dropdown .menu').forEach(m=>m.style.display=''), 0);
      }
    });
  </script>
</body>
</html>
