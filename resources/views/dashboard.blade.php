@extends('layouts.app')

@section('title', '首頁')

@section('content')
<h1>歡迎使用教會系統易</h1>

<div style="margin: 20px 0;">
    <fieldset>
        <legend>系統概覽</legend>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="stat-cell" style="padding: 10px; width: 25%;">
                    <strong>活躍會友</strong><br>
                    <span style="font-size: 24px; color: #0B55C4;">{{ $stats['total_members'] }}</span>
                </td>
                <td class="stat-cell" style="padding: 10px; width: 25%;">
                    <strong>崇拜場次</strong><br>
                    <span style="font-size: 24px; color: #0B55C4;">{{ $stats['worship_services'] }}</span>
                </td>
                <td class="stat-cell" style="padding: 10px; width: 25%;">
                    <strong>今日出席</strong><br>
                    <span style="font-size: 24px; color: #0B55C4;">{{ $stats['today_attendance'] }}</span>
                </td>
                <td class="stat-cell" style="padding: 10px; width: 25%;">
                    <strong>總出席記錄</strong><br>
                    <span style="font-size: 24px; color: #0B55C4;">{{ $stats['total_attendance_records'] }}</span>
                </td>
            </tr>
        </table>
    </fieldset>
</div>

<div style="margin: 20px 0;">
    <fieldset>
        <legend>快速操作</legend>
        <ul style="list-style: none; padding: 10px;">
            <li style="padding: 5px 0;"><a href="{{ route('worship.take') }}">簽到系統</a></li>
            <li style="padding: 5px 0;"><a href="{{ route('members.index') }}">會友資料</a></li>
            <li style="padding: 5px 0;"><a href="{{ route('members.create') }}">新增會友</a></li>
            <li style="padding: 5px 0;"><a href="{{ route('worship.admin') }}">崇拜資料</a></li>
        </ul>
    </fieldset>
</div>
@endsection
