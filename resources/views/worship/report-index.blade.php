@extends('layouts.app')

@section('title', '崇拜報告')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt; <span>崇拜報告</span>
@endsection

@section('content')
<div class="form">
<fieldset class="adminform">
    <legend>崇拜預備</legend>
    <ul class="report_list">
        <li>
            <form method="GET" action="{{ route('worship.report.new-member-attendance') }}" target="_blank">
                <input type="submit" value="臨時會友簽到表">
            </form>
        </li>
        <li>
            <form method="GET" action="{{ route('worship.report.new-membership-card') }}" target="_blank">
                <input type="submit" value="新增會友列表">
            </form>
        </li>
    </ul>
</fieldset>

<fieldset class="adminform">
    <legend>每週報告</legend>
    <ul class="report_list">
        <li>
            <form method="GET" action="{{ route('worship.report.weekly-new-member') }}" target="_blank">
                <input type="submit" value="新朋友報告">
                <select name="week_no">
                    @for($i = 0; $i < 52; $i++)
                        @php $target = time() - ($i * 60 * 60 * 24 * 7); @endphp
                        <option value="{{ date('Y-W', $target) }}">
                            {{ date('Y-m-d', $target - (60*60*24*(date('N', $target)-1))) }} - {{ date('Y-m-d', $target + (60*60*24*(7-date('N', $target)))) }}
                        </option>
                    @endfor
                </select>
            </form>
        </li>
    </ul>
</fieldset>

<fieldset class="adminform">
    <legend>出席報告</legend>
    <ul class="report_list">
        <li>
            <form method="GET" action="{{ route('worship.report.attendance') }}" target="_blank">
                <input type="submit" value="出席報告">
                <select name="week_no">
                    @for($i = 0; $i < 52; $i++)
                        @php $target = time() - ($i * 60 * 60 * 24 * 7); @endphp
                        <option value="{{ date('Y-W', $target) }}">
                            {{ date('Y-m-d', $target - (60*60*24*(date('N', $target)-1))) }} - {{ date('Y-m-d', $target + (60*60*24*(7-date('N', $target)))) }}
                        </option>
                    @endfor
                </select>
                <select name="group_period">
                    <option value="">所有時段</option>
                    @foreach($groupPeriods as $period)
                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                    @endforeach
                </select>
                <select name="member_type">
                    <option value="">所有會友</option>
                    <option value="0">臨時會友</option>
                    <option value="1">會友</option>
                </select>
            </form>
        </li>
        <li>
            <form method="GET" action="{{ route('worship.report.absent') }}" target="_blank">
                <input type="submit" value="缺席報告">
                <select name="week_no">
                    @for($i = 0; $i < 52; $i++)
                        @php $target = time() - ($i * 60 * 60 * 24 * 7); @endphp
                        <option value="{{ date('Y-W', $target) }}">
                            {{ date('Y-m-d', $target - (60*60*24*(date('N', $target)-1))) }} - {{ date('Y-m-d', $target + (60*60*24*(7-date('N', $target)))) }}
                        </option>
                    @endfor
                </select>
                <select name="group_period">
                    <option value="">所有時段</option>
                    @foreach($groupPeriods as $period)
                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                    @endforeach
                </select>
                <select name="member_type">
                    <option value="">所有會友</option>
                    <option value="0">臨時會友</option>
                    <option value="1">會友</option>
                </select>
                <select name="boundary">
                    <option value="3">最近三個月有出席崇拜</option>
                    <option value="6">最近半年有出席崇拜</option>
                    <option value="12">最近一年出席崇拜</option>
                    <option value="">所有</option>
                </select>
            </form>
        </li>
    </ul>
</fieldset>

<fieldset class="adminform">
    <legend>全年報告</legend>
    <ul class="report_list">
        <li>
            <form method="GET" action="{{ route('worship.report.annual') }}" target="_blank">
                <input type="submit" value="全年崇拜出席報告">
                <select name="year">
                    @for($i = date('Y'); $i >= 2008; $i--)
                        <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
                年
            </form>
        </li>
        <li>
            <form method="GET" action="{{ route('worship.report.raw') }}" target="_blank">
                <input type="submit" value="原始數據">
                <select name="year">
                    @for($i = date('Y'); $i >= 2008; $i--)
                        <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
                年
            </form>
        </li>
    </ul>
</fieldset>

<fieldset class="adminform">
    <legend>生日查詢</legend>
    <ul class="report_list">
        <li>
            <form method="GET" action="{{ route('worship.report.birthday') }}" target="_blank">
                <input type="submit" value="生日查詢">
                <select name="week_no">
                    @for($i = -1; $i < 51; $i++)
                        @php $target = time() - ($i * 60 * 60 * 24 * 7); @endphp
                        <option value="{{ date('Y-W', $target + (60*60*24*7)) }}">
                            {{ date('Y-m-d', $target - (60*60*24*(date('N', $target)-1))) }} - {{ date('Y-m-d', $target + (60*60*24*(7-date('N', $target)))) }}
                        </option>
                    @endfor
                </select>
                <select name="boundary">
                    <option value="3">最近三個月有出席崇拜</option>
                    <option value="6">最近半年有出席崇拜</option>
                    <option value="12">最近一年出席崇拜</option>
                    <option value="">所有</option>
                </select>
            </form>
        </li>
    </ul>
</fieldset>
</div>
@endsection
