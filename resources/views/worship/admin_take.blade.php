@extends('layouts.app')

@section('title', '補加出席')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt;
<a href="{{ route('worship.take') }}">簽到系統</a> &gt; <span>補加出席</span>
@endsection

@section('content')
<div id="toolbar-box">
    <div class="t"><div class="t"><div class="t"></div></div></div>
    <div class="m">
        <div class="header">補加出席（崇拜後人手補簽）</div>
        <div class="clr"></div>
    </div>
    <div class="b"><div class="b"><div class="b"></div></div></div>
</div>
<div class="clr"></div>

<div id="element-box">
    <div class="t"><div class="t"><div class="t"></div></div></div>
    <div class="m">

        @if ($submitted)
            <div class="form">
                <fieldset>
                    <legend>處理結果</legend>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 4px; width: 90px;">✅ 成功加入</td>
                            <td style="padding: 4px;">
                                @if (count($added))
                                    {{ count($added) }} 人：{{ implode('、', $added) }}
                                @else
                                    <span style="color: #999;">0 人</span>
                                @endif
                            </td>
                        </tr>
                        <tr style="background: #F8F8F8;">
                            <td style="padding: 4px;">⏭ 已有記錄</td>
                            <td style="padding: 4px;">
                                @if (count($duplicates))
                                    {{ count($duplicates) }} 人（自動跳過）：{{ implode('、', $duplicates) }}
                                @else
                                    <span style="color: #999;">0 人</span>
                                @endif
                            </td>
                        </tr>
                        @if (count($notFound))
                        <tr>
                            <td style="padding: 4px; color: #8a1f11;">❌ 找不到</td>
                            <td style="padding: 4px; color: #8a1f11;">
                                {{ implode('、', $notFound) }} —
                                請核對編號/姓名後修改下方清單再提交
                            </td>
                        </tr>
                        @endif
                        @if (count($ambiguous))
                        <tr style="background: #F8F8F8;">
                            <td style="padding: 4px; color: #8a1f11;">❓ 多個符合</td>
                            <td style="padding: 4px; color: #8a1f11;">
                                @foreach ($ambiguous as $a)
                                    「{{ $a['input'] }}」符合：{{ $a['options'] }}<br>
                                @endforeach
                                請改用會友編號輸入以避免重名
                            </td>
                        </tr>
                        @endif
                    </table>
                </fieldset>
            </div>
        @endif

        <div class="form">
            <fieldset>
                <legend>貼上會友清單（一行一個，支援由 Excel / Google Sheets 直接複製貼上）</legend>
                <form method="POST" action="{{ route('worship.attendance.admin-store') }}">
                    @csrf
                    <div class="row">
                        <label>崇拜</label>
                        <select name="worship_id" required>
                            @foreach ($worships as $w)
                                <option value="{{ $w->id }}" {{ ($old['worship_id'] ?? null) == $w->id ? 'selected' : '' }}>
                                    {{ $w->weekly_label }} {{ $w->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <label>出席日期</label>
                        <input type="date" name="attendance_date" required
                               value="{{ $old['attendance_date'] ?? now()->toDateString() }}"
                               max="{{ now()->toDateString() }}">
                        <span style="color: #666; margin-left: 8px;">只可選今天或以前</span>
                    </div>
                    <div class="row">
                        <label>清單</label>
                        <textarea name="entries" rows="12" style="width: 420px; font-family: monospace;"
                                  placeholder="每行一個會友編號或姓名，例如：&#10;1001&#10;1002&#10;陳大文&#10;&#10;亦可直接由試算表複製整欄貼上（自動取第一欄）">{{ $old['entries'] ?? '' }}</textarea>
                    </div>
                    <div class="row buttons">
                        <input type="submit" value="加入出席記錄">
                        <a href="{{ route('worship.take') }}" style="margin-left: 12px;">返回簽到系統</a>
                    </div>
                </form>
            </fieldset>
        </div>

        <div class="form">
            <fieldset>
                <legend>說明</legend>
                <ul style="margin: 4px 0 4px 20px; color: #444;">
                    <li>每行一個項目：會友編號（優先）或會友姓名（需完全相同，重名請用編號）</li>
                    <li>支援由 Excel / Google Sheets 複製整欄或整列貼上，系統會自動取第一格</li>
                    <li>該日已有出席記錄的會友會自動跳過，不會重複</li>
                    <li>記錄時間為補簽日期＋現時時間，報表按日期統計</li>
                    <li>如需刪除錯誤記錄，請到「崇拜出席資料」頁面刪除</li>
                </ul>
            </fieldset>
        </div>

        <div class="clr"></div>
    </div>
    <div class="b"><div class="b"><div class="b"></div></div></div>
</div>
@endsection
