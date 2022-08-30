<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="{{config("app.description")}}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{config("app.url")}}">
    <meta property="og:title" content="{{config("app.title")}}">
    <meta property="og:image" content="/images/logo-modify.jpg">
    <meta property="og:description" content="{{config("app.description")}}">
    <meta property="og:site_name" content="{{config("app.title")}}">
    <meta property="og:locale" content="fr_FR">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="{{config("app.url")}}">
    <meta name="twitter:creator" content="@enpix">
    <meta name="twitter:url" content="{{config("app.url")}}">
    <meta name="twitter:title" content="{{config("app.title")}}">
    <meta name="twitter:description" content="{{config("app.description")}}">
    <meta name="twitter:image" content="/images/logo-modify.jpg">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href={{ asset('css/bootstrap.min.css') }}>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
    <link rel="icon" type="image/png" href="/favicon/favicon-16x16.png">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <!-- Icons -->
    <link href="/fontawesome/css/all.min.css" rel="stylesheet">

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <script>
        var translations = {!! \Cache::get('translations') !!};
    </script>
    <script src="/js/svg.min.js"></script>
	<script>
        //SVG.js Cant be import in app.js (version 2 used)
        let mapWidth = 17;
        let mapHeight = 14;
        @if (isset($game) && isset($map))
            let mapTiles = '{!! $map !!}';
            let gameId = {{$game->id}};
        @endif
	</script>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <img src="/images/logo.jpg" style="width:80px;padding-right:10px;"/>
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4" id="main_app">
            @yield('content')
        </main>
    </div>
    <script src="/js/app.js"></script>

    <footer style="position:fixed;bottom:0; margin:auto;text-align:center;background:#fff;height:50px;padding:15px;width:100%">
        Règles du jeu <a href="https://youtu.be/WLSg3jQa570" target="_blank">📺</a>&nbsp;<a href="http://file.trictrac.net/file-53fd903cbe6f2.pdf" target="_blank">📁</a> -
        <a href="https://www.gameandme.fr" target="_blank">Yohann Nizon - Développeur PHP </a> - <a target="_blank" href="https://github.com/ynizon/lemming">https://github.com/ynizon/lemming</a>
    </footer>
</body>
</html>
