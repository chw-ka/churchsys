@extends('layouts.app')

@section('title', '登入')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<div id="element-box" class="login">
    <div class="t"><div class="t"><div class="t"></div></div></div>
    <div class="form m">
        <h1>登入</h1>
        <form id="form-login" method="POST" action="{{ route('login') }}">
            @csrf
            <div id="section-box">
                <div class="t"><div class="t"><div class="t"></div></div></div>
                <div class="m">
                    <p id="form-login-username">
                        <label for="username">用戶名</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" autofocus>
                        @error('username') <span class="error">{{ $message }}</span> @enderror
                    </p>
                    <p id="form-login-password">
                        <label for="password">密碼</label>
                        <input type="password" name="password" id="password">
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                    </p>
                    <p id="form-login-lang" style="clear: both;">
                        <input type="checkbox" name="remember" id="remember" value="1">
                        <label for="remember" style="display:inline;">記住我</label>
                    </p>
                    <div class="button_holder">
                        <div class="button">
                            <input type="submit" value="Login">
                        </div>
                    </div>
                    <div class="clr"></div>
                </div>
                <div class="b"><div class="b"><div class="b"></div></div></div>
            </div>
            <p>歡迎使用教會系統易</p>
            <div id="lock"></div>
            <div class="clr"></div>
        </form>
    </div>
    <div class="b"><div class="b"><div class="b"></div></div></div>
</div>
<div class="clr"></div>
@endsection
