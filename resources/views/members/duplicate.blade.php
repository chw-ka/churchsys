@extends('layouts.app')

@section('title', '合併重覆會友')

@section('content')
<h1>合併重覆會友</h1>

@if(empty($duplicateList))
    <p>沒有找到重覆的會友資料。</p>
@else
    @foreach($duplicateList as $group)
        <fieldset style="margin-bottom: 20px;">
            <legend>重覆會友: {{ $group->first()->name }} ({{ $group->count() }} 筆)</legend>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f0f0f0;">
                        <th style="padding: 5px; border: 1px solid #ccc;">編號</th>
                        <th style="padding: 5px; border: 1px solid #ccc;">姓名</th>
                        <th style="padding: 5px; border: 1px solid #ccc;">生日</th>
                        <th style="padding: 5px; border: 1px solid #ccc;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group as $member)
                    <tr>
                        <td style="padding: 5px; border: 1px solid #ccc;">{{ $member->code }}</td>
                        <td style="padding: 5px; border: 1px solid #ccc;">{{ $member->name }}</td>
                        <td style="padding: 5px; border: 1px solid #ccc;">{{ $member->birthday }}</td>
                        <td style="padding: 5px; border: 1px solid #ccc;">
                            <a href="{{ route('members.show', $member->id) }}">查看</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </fieldset>
    @endforeach
@endif
@endsection
