@extends('layouts.app')

@section('title', '會友出席資料')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>會友出席資料</span>
@endsection

@section('content')
<h1>會友出席資料</h1>

<!-- Search -->
<div class="search-form" style="padding: 10px; margin: 10px 0; background: #eee;">
    <form method="GET" action="{{ route('worship.attendance.by-member') }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="搜尋姓名或編號..." style="padding: 4px; width: 250px;">
        <input type="submit" value="搜尋">
    </form>
</div>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 5px; border: 1px solid #ccc;">會友 (編號)</th>
            <th style="padding: 5px; border: 1px solid #ccc;">最後出席</th>
            <th style="padding: 5px; border: 1px solid #ccc;">近兩月</th>
            <th style="padding: 5px; border: 1px solid #ccc;">近六月</th>
            <th style="padding: 5px; border: 1px solid #ccc;">近一年</th>
            <th style="padding: 5px; border: 1px solid #ccc;">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse($members as $member)
        <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f9f9f9;' : '' }}">
            <td style="padding: 5px; border: 1px solid #ccc;">
                <a href="{{ route('members.show', $member->id) }}">{{ $member->name }} ({{ $member->code }})</a>
            </td>
            <td style="padding: 5px; border: 1px solid #ccc;">
                {{ $member->last_date ? \Carbon\Carbon::parse($member->last_date)->format('Y-m-d') : '--' }}
            </td>
            <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">{{ $member->count_2m }}</td>
            <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">{{ $member->count_6m }}</td>
            <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">{{ $member->count_1y }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">
                <a href="{{ route('members.show', $member->id) }}">查看</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="padding: 10px; text-align: center;">沒有找到會友資料</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 10px;">
    {{ $members->appends(request()->query())->links() }}
</div>
@endsection
