@extends('layouts.app')

@section('title', '崇拜報告')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>崇拜報告</span>
@endsection

@section('content')
<h1>崇拜報告</h1>

<form method="GET" action="{{ route('worship.report') }}" style="margin-bottom: 20px;">
    <fieldset>
        <legend>選擇日期範圍</legend>
        <div class="row">
            <label>開始日期</label>
            <input type="date" name="start" value="{{ $start }}">
        </div>
        <div class="row">
            <label>結束日期</label>
            <input type="date" name="end" value="{{ $end }}">
        </div>
        <div class="row buttons">
            <input type="submit" value="查詢">
        </div>
    </fieldset>
</form>

@if(isset($report))
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f0f0f0;">
            <th style="padding: 5px; border: 1px solid #ccc;">日期</th>
            @foreach($worships as $w)
                <th style="padding: 5px; border: 1px solid #ccc;">{{ $w->name }}</th>
            @endforeach
            <th style="padding: 5px; border: 1px solid #ccc;">總計</th>
        </tr>
    </thead>
    <tbody>
        @foreach($report as $row)
        <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f9f9f9;' : '' }}">
            <td style="padding: 5px; border: 1px solid #ccc;">{{ $row['date'] }}</td>
            @foreach($worships as $w)
                <td style="padding: 5px; border: 1px solid #ccc; text-align: center;">
                    {{ $row['counts'][$w->id] ?? 0 }}
                </td>
            @endforeach
            <td style="padding: 5px; border: 1px solid #ccc; text-align: center; font-weight: bold;">
                {{ $row['total'] }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
