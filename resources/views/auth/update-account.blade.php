@extends('layouts.app')

@section('title', '更新帳戶')

@section('content')
<h1>更新帳戶</h1>

<div class="form">
    @if ($errors->any())
    <div class="flash-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <fieldset>
            <legend>更改密碼</legend>
            <p>用戶名: <strong>{{ $user->username }}</strong></p>

            <div class="row">
                <label>新密碼</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="row">
                <label>確認新密碼</label>
                <input type="password" name="new_password_confirmation" required>
            </div>
        </fieldset>

        <div class="row buttons" style="padding: 10px 0;">
            <input type="submit" value="更新密碼">
        </div>
    </form>
</div>
@endsection
