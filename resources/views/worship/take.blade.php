@extends('layouts.app')

@section('title', '簽到系統')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>簽到系統</span>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/takeAttendance.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/jclock/jquery.jclock.js') }}"></script>
<script src="{{ asset('js/worship/takeAttendance.js') }}"></script>
@endpush

@section('content')
<div id="toolbar-box">
    <div class="t"><div class="t"><div class="t"></div></div></div>
    <div class="m">
        <div class="header">
            簽到系統
            @foreach($worships as $w)
                [{{ $w->name }}]
            @endforeach
            {{ now()->format('Y-m-d') }}
            <span id="timer" rel="{{ time() }}"></span>
        </div>
        <div class="clr"></div>
    </div>
    <div class="b"><div class="b"><div class="b"></div></div></div>
</div>
<div class="clr"></div>

<div id="element-box">
    <div class="t"><div class="t"><div class="t"></div></div></div>
    <div class="m">
        <div class="col-main" style="width: 70%; float: left;">
            <!-- Take Attendance Form -->
            <div id="take_attendance_form" class="form">
                <fieldset>
                    <legend>請輸入 會友編號/姓名</legend>
                    <form id="attendance-form">
                        @csrf
                        <div class="row">
                            <label>會友編號</label>
                            <input type="text" name="member_code" id="member_code_input" size="40"
                                   autocomplete="off" autofocus>
                        </div>
                        <div class="row">
                            <label>崇拜</label>
                            <select name="worship_id" id="worship_select">
                                @foreach($worships as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row buttons">
                            <input type="submit" value="送出">
                        </div>
                    </form>
                </fieldset>
            </div>

            <!-- Welcome Message -->
            <div class="form">
                <fieldset>
                    <legend>歡迎詞</legend>
                    <div id="welcome_message" style="color: red; font-size: 36px;"></div>
                </fieldset>
            </div>
        </div>

        <div class="col-side" style="width: 30%; float: left;">
            <!-- Stats -->
            <div class="form">
                <fieldset>
                    <legend>簽到統計</legend>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f0f0f0;">
                                <th style="padding: 3px; border: 1px solid #ccc;">崇拜</th>
                                <th style="padding: 3px; border: 1px solid #ccc;">會友</th>
                                <th style="padding: 3px; border: 1px solid #ccc;">新朋友</th>
                                <th style="padding: 3px; border: 1px solid #ccc;">總人數</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($worships as $w)
                            @php
                                $count = $todayCounts->get($w->id, 0);
                            @endphp
                            <tr>
                                <td style="padding: 3px; border: 1px solid #ccc;">{{ $w->name }}</td>
                                <td style="padding: 3px; border: 1px solid #ccc; text-align: center;">{{ $count }}</td>
                                <td style="padding: 3px; border: 1px solid #ccc; text-align: center;">0</td>
                                <td style="padding: 3px; border: 1px solid #ccc; text-align: center;">{{ $count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </fieldset>
            </div>

            <!-- Today's attendance list -->
            <div class="form scroll-box" style="height: 300px; overflow-y: auto;">
                <fieldset>
                    <legend>已簽到列表</legend>
                    <div id="attendance-list">
                        <p style="color: #999; text-align: center;">暫無簽到記錄</p>
                    </div>
                </fieldset>
            </div>
        </div>
        <div class="clr"></div>
    </div>
    <div class="b"><div class="b"><div class="b"></div></div></div>
</div>

<script>
$(function() {
    // Auto-submit attendance form
    $('#attendance-form').on('submit', function(e) {
        e.preventDefault();
        var code = $('#member_code_input').val().trim();
        if (!code) return;

        $.ajax({
            url: '{{ route("worship.attendance.checkin") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                member_code: code,
                worship_id: $('#worship_select').val(),
                attendance_date: '{{ now()->toDateString() }}'
            },
            success: function(res) {
                if (res.success) {
                    $('#welcome_message').text(res.message || '簽到成功！');
                    $('#member_code_input').val('').focus();
                    // Refresh attendance list
                    if (res.html) {
                        $('#attendance-list').html(res.html);
                    }
                } else {
                    $('#welcome_message').text(res.message || '簽到失敗');
                }
            },
            error: function() {
                $('#welcome_message').text('系統錯誤');
            }
        });
    });
});
</script>
@endsection
