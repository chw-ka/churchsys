@extends('layouts.app')

@section('title', '會友資料')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>會友資料</span>
@endsection

@section('content')
<h1>會友管理</h1>

<div style="margin-bottom: 10px;">
    <a href="{{ route('members.create') }}" style="color: #0B55C4; font-weight: bold;">+ 新增會友</a>
</div>

<!-- Search -->
<div class="search-form" style="padding: 10px; margin: 10px 0; background: #eee;">
    <form method="GET" action="{{ route('members.index') }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="搜尋姓名或編號..." style="padding: 4px; width: 250px;">
        <input type="submit" value="搜尋">
    </form>
</div>

<table id="member-grid" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 5px; border: 1px solid #ccc;">會友 (編號)</th>
            <th style="padding: 5px; border: 1px solid #ccc;">姓名</th>
            <th style="padding: 5px; border: 1px solid #ccc;">會友類別</th>
            <th style="padding: 5px; border: 1px solid #ccc;">生日</th>
            <th style="padding: 5px; border: 1px solid #ccc;">操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse($members as $member)
        <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f9f9f9;' : '' }}">
            <td style="padding: 5px; border: 1px solid #ccc;">
                <a href="{{ route('members.show', $member->id) }}">{{ $member->name }} ({{ $member->code }})</a>
            </td>
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $member->name }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $member->accountTypeLabel }}</td>
            <td style="padding: 5px; border: 1px solid #ccc;">
                @if($member->birthday && $member->birthday !== '0000-00-00' && $member->birthday !== '1970-01-01')
                    {{ \Carbon\Carbon::parse($member->birthday)->format('d/m') }}
                @else
                    --
                @endif
            </td>
            <td style="padding: 5px; border: 1px solid #ccc;">
                <a href="{{ route('members.show', $member->id) }}">查看</a> |
                <a href="{{ route('members.edit', $member->id) }}">編輯</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="padding: 10px; text-align: center;">沒有找到會友資料</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 10px;">
    {{ $members->appends(request()->query())->links() }}
</div>
@endsection
