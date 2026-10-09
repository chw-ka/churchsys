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
                        <div class="row" style="position:relative;">
                            <label>會友編號/姓名</label>
                            <input type="text" name="member_code" id="member_code_input" size="40"
                                   autocomplete="off" autofocus>
                            {{-- live suggestion dropdown (name or code) --}}
                            <ul id="code_autocomplete"
                                style="position:absolute;z-index:50;background:#fff;border:1px solid #999;
                                       list-style:none;margin:0;padding:0;display:none;min-width:220px;"></ul>
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
                        <tbody id="stat-rows">
                            @foreach($worships as $w)
                            @php
                                $count = $todayCounts->get($w->id, 0);
                            @endphp
                            <tr data-worship-id="{{ $w->id }}">
                                <td style="padding: 3px; border: 1px solid #ccc;">{{ $w->name }}</td>
                                <td class="js-count-member" style="padding: 3px; border: 1px solid #ccc; text-align: center;">{{ $count }}</td>
                                <td style="padding: 3px; border: 1px solid #ccc; text-align: center;">0</td>
                                <td class="js-count-total" style="padding: 3px; border: 1px solid #ccc; text-align: center;">{{ $count }}</td>
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
                    <div id="attendance-list">{!! $todayList !!}</div>
                </fieldset>
            </div>
        </div>
        <div class="clr"></div>
    </div>
    <div class="b"><div class="b"><div class="b"></div></div></div>
</div>

<script>
$(function() {
    var $input = $('#member_code_input');
    var $ac = $('#code_autocomplete');
    var $form = $('#attendance-form');
    var checking = false;
    var acIndex = -1;

    /* ============ Check-in submit ============ */
    $form.on('submit', function(e) {
        e.preventDefault();
        if (checking) return;
        var code = $input.val().trim();
        if (!code) return;

        checking = true;
        $.ajax({
            url: '{{ route("worship.attendance.checkin") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                member_code: code,
                worship_id: $('#worship_select').val()
            },
            success: function(res) {
                hideAc();
                if (res.success) {
                    $('#welcome_message').text(res.message || '簽到成功！');
                    $input.val('').focus();
                    refreshPanels(res);
                } else {
                    $('#welcome_message').text(res.message || '簽到失敗');
                }
            },
            statusCode: {
                404: function(xhr) { showMsg(xhr, '編號或姓名找不到'); },
                409: function(xhr) { showMsg(xhr, '今天已簽到'); },
                422: function(xhr) { showCandidates(xhr); }
            },
            complete: function() { checking = false; }
        });
    });

    function showMsg(xhr, fallback) {
        var msg = fallback;
        try { msg = (JSON.parse(xhr.responseText) || {}).message || fallback; } catch (err) {}
        $('#welcome_message').text(msg);
    }

    /* Ambiguous name: let the usher pick the right member */
    function showCandidates(xhr) {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch (err) { return; }
        if (!res || !res.candidates || !res.candidates.length) {
            $('#welcome_message').text(res.message || '請確認輸入');
            return;
        }
        var $box = $('#welcome_message').empty();
        $('<div style="font-size:18px;margin-bottom:6px;"></div>').text(res.message || '同名會友多於一位，請點選：').appendTo($box);
        $.each(res.candidates, function(i, c) {
            var $btn = $('<button type="button" style="font-size:20px;margin:4px 8px 4px 0;padding:8px 14px;cursor:pointer;"></button>')
                .text(c.name + (c.code ? ' (' + c.code + ')' : ''))
                .on('click', function() {
                    checking = true;
                    $.ajax({
                        url: '{{ route("worship.attendance.checkin") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            member_id: c.id,
                            member_code: c.code || c.name,
                            worship_id: $('#worship_select').val()
                        },
                        success: function(res2) {
                            if (res2.success) {
                                $('#welcome_message').text(res2.message || '簽到成功！');
                                $input.val('').focus();
                                refreshPanels(res2);
                            }
                        },
                        statusCode: { 409: function(x) { showMsg(x, '今天已簽到'); } },
                        complete: function() { checking = false; }
                    });
                });
            $box.append($btn);
        });
    }

    /* ============ Live name/code suggestions ============ */
    var acTimer = null;
    $input.on('input', function() {
        var q = $input.val().trim();
        clearTimeout(acTimer);
        if (!q) { hideAc(); return; }
        acTimer = setTimeout(function() { fetchSuggestions(q); }, 250);
    });

    $input.on('keydown', function(e) {
        var $items = $ac.find('li');
        if (!$items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            acIndex += (e.key === 'ArrowDown' ? 1 : -1);
            if (acIndex < 0) acIndex = $items.length - 1;
            if (acIndex >= $items.length) acIndex = 0;
            $items.removeClass('ac-active').css('background', '');
            $items.eq(acIndex).addClass('ac-active').css('background', '#e8f0fe');
        } else if (e.key === 'Enter') {
            if (acIndex >= 0 && $ac.is(':visible')) {
                e.preventDefault();
                $items.eq(acIndex).trigger('click');
            }
        } else if (e.key === 'Escape') {
            hideAc();
        }
    });

    function fetchSuggestions(q) {
        $.getJSON('{{ route("members.autocomplete") }}', { q: q }, function(items) {
            if (!items || !items.length) { hideAc(); return; }
            acIndex = -1;
            $ac.empty().show();
            $.each(items, function(i, item) {
                $('<li style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #eee;"></li>')
                    .text(item.label)
                    .data('value', item.value)
                    .on('mouseenter', function() { $(this).css('background', '#e8f0fe'); })
                    .on('mouseleave', function() { $(this).css('background', ''); })
                    .on('click', function() {
                        $input.val($(this).data('value'));
                        hideAc();
                        $form.trigger('submit');
                    })
                    .appendTo($ac);
            });
        }).fail(function() { hideAc(); });
    }

    function hideAc() { $ac.hide().empty(); acIndex = -1; }

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#member_code_input, #code_autocomplete').length) hideAc();
    });

    /* ============ Cross-device sync (polling) ============ */
    function refreshPanels(res) {
        if (res.html) $('#attendance-list').html(res.html);
        if (res.counts) {
            $.each(res.counts, function(worshipId, count) {
                var $row = $('#stat-rows tr[data-worship-id="' + worshipId + '"]');
                $row.find('.js-count-member').text(count);
                $row.find('.js-count-total').text(count);
            });
        }
    }

    function poll() {
        if (checking || document.visibilityState !== 'visible') return;
        $.getJSON('{{ route("worship.attendance.take-data") }}', {
            worship_id: $('#worship_select').val()
        }, function(res) {
            if (res && res.success && !checking) refreshPanels(res);
        }).fail(function() { /* transient network issue; next poll retries */ });
    }

    setInterval(poll, 10000);
});
</script>
@endsection
