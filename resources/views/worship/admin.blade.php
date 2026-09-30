@extends('layouts.app')

@section('title', '崇拜資料')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>崇拜資料</span>
@endsection

@section('content')
<h1>崇拜資料管理</h1>

<div style="margin-bottom: 10px;">
    <a href="{{ route('worship.create') }}" style="color: #0B55C4; font-weight: bold;">+ 新增崇拜</a>
</div>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 5px; border: 1px solid #ccc;">名稱</th>
            <th style="padding: 5px; border: 1px solid #ccc;">每週</th>
            <th style="padding: 5px; border: 1px solid #ccc;">開始時間</th>
            <th style="padding: 5px; border: 1px solid #ccc;">結束時間</th>
            <th style="padding: 5px; border: 1px solid #ccc;">狀態</th>
            <th style="padding: 5px; border: 1px solid #ccc;">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse($worships as $worship)
        <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f9f9f9;' : '' }}">
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $worship->name }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $worship->weeklyLabel }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $worship->start_time }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $worship->end_time }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $worship->state == 1 ? '活躍' : '停用' }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">
                <a href="{{ route('worship.edit', $worship->id) }}">編輯</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="padding: 10px; text-align: center;">沒有崇拜資料</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
