<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0B55C4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="教會系統">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">

    <title>@yield('title', '教會系統易')</title>

    <!-- Blueprint CSS framework -->
    <link rel="stylesheet" href="{{ asset('css/screen.css') }}" media="screen, projection">
    <link rel="stylesheet" href="{{ asset('css/print.css') }}" media="print">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rounded.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gridview.css') }}">
    <link rel="stylesheet" href="{{ asset('js/jqueryslidemenu/jqueryslidemenu.css') }}">

    {{-- Responsive overlay: must stay AFTER all legacy CSS --}}
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    @stack('styles')

    <!-- jQuery -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jqueryslidemenu/jqueryslidemenu.js') }}"></script>

    {{-- Responsive behaviour + PWA registration: must stay AFTER the legacy menu script --}}
    <script src="{{ asset('js/app-responsive.js') }}" defer></script>
    @stack('scripts')
</head>
<body>
    <div id="border-top" class="h_teal">
        <div>
            <div>
                <span class="title">教會系統易</span>
            </div>
        </div>
    </div>

    <div id="header-box">
        <div id="myslidemenu" class="jqueryslidemenu">
            <ul id="menu">
                <li><a href="{{ route('dashboard') }}">首頁</a></li>
                @auth
                <li>
                    <a href="{{ route('worship.take') }}">簽到系統</a>
                </li>
                <li>
                    <a href="#">崇拜資料</a>
                    <ul>
                        <li><a href="{{ route('worship.attendance.by-member') }}">會友出席資料</a></li>
                        <li><a href="{{ route('worship.attendance.by-worship') }}">崇拜出席資料</a></li>
                        <li><a href="{{ route('worship.attendance.admin-take') }}">補加出席</a></li>
                        <li><a href="{{ route('worship.admin') }}">崇拜資料</a></li>
                        <li><a href="{{ route('worship.report') }}">崇拜報告</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">會友</a>
                    <ul>
                        <li><a href="{{ route('members.index') }}">會友資料</a></li>
                        <li><a href="{{ route('members.duplicate') }}">合併重覆會友</a></li>
                    </ul>
                </li>
                <li><a href="{{ route('members.update-account') }}">更新帳戶</a></li>
                <li class="install-item"><a href="#">📲 安裝到裝置</a></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:#333;font:bold 11px Arial;padding:0.35em 1em;cursor:pointer;">登出</button>
                    </form>
                </li>
                @endauth
            </ul>
        </div>
        <div class="clr"></div>
    </div>

    <div id="content-box">
        <div id="breadcrumbs">
            @hasSection('breadcrumbs')
                @yield('breadcrumbs')
            @else
                <a href="{{ route('dashboard') }}">首頁</a>
            @endif
        </div>
        <div class="clr"></div>
        <div class="padding">
            @if (session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="flash-error">{{ session('error') }}</div>
            @endif
            @if (session('notice'))
                <div class="flash-notice">{{ session('notice') }}</div>
            @endif
            @yield('content')
        </div>
    </div>

    <div id="border-bottom"><div><div></div></div></div>

    <div id="footer">
        <p class="copyright">
            ChurchSys 教會系統易&copy;基督教宣道會活石堂
        </p>
    </div>
</body>
</html>
