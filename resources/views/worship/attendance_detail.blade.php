@extends('layouts.app')

@section('title', '崇拜出席名單')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt;
<a href="{{ route('worship.attendance.by-worship') }}">崇拜出席資料</a> &gt;
<span>出席名單</span>
@endsection

@section('content')
<h1>崇拜出席名單</h1>

<p style="margin: 0 0 10px;">
    <strong>{{ $start }} ~ {{ $end }}</strong>
    @if($worshipName)
        ・{{ $worshipName }}
    @else
        ・全部崇拜
    @endif
    ・共 <strong>{{ $attendances->total() }}</strong> 筆出席紀錄
</p>

<div class="search-form" style="padding: 10px; margin: 10px 0; background: #eee;">
    <form method="GET" action="{{ route('worship.attendance.by-worship-detail') }}">
        <input type="hidden" name="start" value="{{ $start }}">
        <input type="hidden" name="end" value="{{ $end }}">

        <label for="search">搜尋：</label>
        <input type="text" id="search" name="search" value="{{ $search }}" placeholder="姓名或編號..." style="padding: 4px; width: 180px;">

        <label for="type" style="margin-left: 10px;">類別：</label>
        <select name="type" id="type">
            @foreach([
                'all'         => '全部',
                'member'      => '會友＋同工',
                'member_only' => '只顯示會友',
                'co_member'   => '只顯示同工',
                'new'         => '只顯示新朋友（臨時會員）',
            ] as $value => $label)
                <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <label for="worship_id" style="margin-left: 10px;">崇拜：</label>
        <select name="worship_id" id="worship_id">
            <option value="">全部</option>
            @foreach($worships as $w)
                <option value="{{ $w->id }}" {{ (int) $worshipId === $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
            @endforeach
        </select>

        <input type="submit" value="篩選" style="margin-left: 6px;">
        <a href="{{ route('worship.attendance.by-worship-detail', ['start' => $start, 'end' => $end]) }}" style="margin-left: 8px;">清除</a>
    </form>
</div>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 5px; border: 1px solid #ccc;">日期</th>
            <th style="padding: 5px; border: 1px solid #ccc;">會友 (編號)</th>
            <th style="padding: 5px; border: 1px solid #ccc;">類別</th>
            <th style="padding: 5px; border: 1px solid #ccc;">崇拜</th>
            <th style="padding: 5px; border: 1px solid #ccc;">簽到時間</th>
        </tr>
    </thead>
    <tbody>
        @forelse($attendances as $attendance)
            @php $member = $attendance->member; @endphp
            <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f9f9f9;' : '' }}">
                <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">
                    {{ $attendance->attendance_date?->format('Y-m-d') }}
                </td>
                <td style="padding: 5px; border: 1px solid #ccc;">
                    @if($member)
                        <a href="{{ route('members.show', $member->id) }}">{{ $member->name }} ({{ $member->code }})</a>
                        @if($member->state !== \App\Models\Member::STATE_ACTIVE)
                            <span style="color: #999;">（已刪除）</span>
                        @endif
                    @else
                        <span style="color: #999;">（已刪除會友）</span>
                    @endif
                </td>
                <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">
                    {{ $member?->account_type_label ?? '--' }}
                </td>
                <td style="padding: 5px; border: 1px solid #ccc;">{{ $attendance->worship?->name ?? '--' }}</td>
                <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">
                    {{ $attendance->attendance_date?->format('H:i') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="padding: 10px; text-align: center;">此期間沒有符合條件的出席紀錄</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 10px;">
    {{ $attendances->appends(request()->query())->links() }}
</div>
@endsection
