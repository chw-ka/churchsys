@extends('layouts.app')

@section('title', '崇拜出席資料')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>崇拜出席資料</span>
@endsection

@section('content')
<h1>崇拜出席資料</h1>

<form method="GET" action="{{ route('worship.attendance.by-worship') }}" style="margin-bottom: 15px;">
    <label>年份：</label>
    <select name="year" onchange="this.form.submit()">
        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
</form>

<table style="width: 100%; border-collapse: collapse;" data-freeze-cols="2">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 5px; border: 1px solid #ccc;">週</th>
            <th style="padding: 5px; border: 1px solid #ccc;">日期（該週出席日）</th>
            @foreach($worships as $w)
                <th style="padding: 5px; border: 1px solid #ccc;">{{ $w->name }}</th>
            @endforeach
            <th style="padding: 5px; border: 1px solid #ccc;">總計</th>
        </tr>
    </thead>
    <tbody>
        @forelse($weeklyData as $row)
        <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f9f9f9;' : '' }}">
            <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">
                {{ $row->week_no }}
            </td>
            <td style="padding: 5px; border: 1px solid #ccc;">
                {{ $row->week_start }} ~ {{ $row->week_end }}
            </td>
            @php
                // Counts for this whole week, per worship (pre-computed in the controller)
                $rowCounts = $row->counts ?? [];
            @endphp
            @foreach($worships as $w)
                <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">
                    {{ $rowCounts[$w->id] ?? 0 }}
                </td>
            @endforeach
            <td style="padding: 5px; border: 1px solid #ccc; text-align: center; font-weight: bold;">
                {{ array_sum($rowCounts) }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($worships) + 3 }}" style="padding: 10px; text-align: center;">沒有出席記錄</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 10px;">
    {{ $weeklyData->appends(request()->query())->links() }}
</div>
@endsection
