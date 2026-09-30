@extends('layouts.app')

@section('title', $member->name . ' (' . $member->code . ')')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt;
<a href="{{ route('members.index') }}">會友</a> &gt;
<span>{{ $member->name }}</span>
@endsection

@section('content')
@php
    $photoPath = storage_path('app/public/file/member/' . $member->code . '.jpg');
    $image = file_exists($photoPath) ? asset('storage/file/member/' . $member->code . '.jpg') : asset('images/anonymous.gif');
@endphp

<img src="{{ $image }}" width="200" style="float: right; margin: 0 0 10px 10px;">
<h1>{{ $member->name }} ({{ $member->code }})</h1>

<div style="clear: both; margin-bottom: 10px;">
    <a href="{{ route('members.index') }}">會友列表</a> |
    <a href="{{ route('members.create') }}">新增會友</a> |
    <a href="{{ route('members.edit', $member->id) }}">更改會友</a>
</div>

<fieldset>
    <legend>個人資料</legend>
    <table style="width: 100%;">
        <tr><td style="width: 150px; font-weight: bold; padding: 3px;">編號</td><td>{{ $member->code }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">姓名</td><td>{{ $member->name }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">英文名</td><td>{{ $member->english_name ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">會友類別</td><td>{{ $member->accountTypeLabel }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">電郵</td><td>{{ $member->email ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">備註</td><td>{{ $member->remarks ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">性別</td><td>{{ $member->genderLabel }}</td></tr>
        <tr>
            <td style="font-weight: bold; padding: 3px;">生日</td>
            <td>
                @if($member->birthday && $member->birthday !== '0000-00-00' && $member->birthday !== '1970-01-01')
                    {{ \Carbon\Carbon::parse($member->birthday)->format('d/m/Y') }}
                @else
                    --
                @endif
            </td>
        </tr>
    </table>
</fieldset>

<fieldset>
    <legend>信仰狀況</legend>
    <table style="width: 100%;">
        <tr><td style="width: 150px; font-weight: bold; padding: 3px;">信主</td><td>{{ $member->believe ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">信主日期</td><td>{{ $member->believe_date && $member->believe_date !== '0000-00-00' ? $member->believe_date : '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">受浸</td><td>{{ $member->baptized ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">受浸日期</td><td>{{ $member->baptized_date && $member->baptized_date !== '0000-00-00' ? $member->baptized_date : '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">新來賓</td><td>{{ $member->new_card ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">到訪日期</td><td>{{ $member->arrived_date && $member->arrived_date !== '0000-00-00' ? $member->arrived_date : '--' }}</td></tr>
    </table>
</fieldset>

<fieldset>
    <legend>聯絡資料</legend>
    <table style="width: 100%;">
        <tr><td style="width: 150px; font-weight: bold; padding: 3px;">住宅電話</td><td>{{ $member->contact_home ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">手提電話</td><td>{{ $member->contact_mobile ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">辦公電話</td><td>{{ $member->contact_office ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">其他聯絡</td><td>{{ $member->contact_others ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">地區</td><td>{{ $member->address_district ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">屋苑</td><td>{{ $member->address_estate ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">樓宇</td><td>{{ $member->address_house ?: '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">單位</td><td>{{ $member->address_flat ?: '--' }}</td></tr>
    </table>
</fieldset>

@if(isset($worshipStats))
<fieldset>
    <legend>崇拜出席情況</legend>
    <table style="width: 100%;">
        <tr><td style="width: 200px; font-weight: bold; padding: 3px;">最後出席</td><td>{{ $worshipStats['last_date'] ?? '--' }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">近兩個月次數</td><td>{{ $worshipStats['two_month_count'] ?? 0 }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">近六個月次數</td><td>{{ $worshipStats['six_month_count'] ?? 0 }}</td></tr>
        <tr><td style="font-weight: bold; padding: 3px;">近一年次數</td><td>{{ $worshipStats['year_count'] ?? 0 }}</td></tr>
    </table>
</fieldset>
@endif
@endsection
