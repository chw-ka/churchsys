<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
body { font: normal 10pt Arial, Helvetica, sans-serif; margin: 10px; }
.shttitle td { font-size: 16px; font-weight: bold; }
.pcode { font-size: 10px; }
.tbltitle td { border: 2px solid #000; font-weight: bold; font-size: 12px; }
.tblcontent td { border: 1px solid #000; font-weight: normal; font-size: 12px; }

/* responsive additions (mobile-friendly without changing print layout) */
.table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
@media (max-width: 767px) {
    body { font-size: 11px; margin: 6px; }
    #tblMain td, #tblMain th { padding: 3px 2px; }
}
</style>
</head>
<body>
<table border=0 cellpadding=0 cellspacing=0 id='tblMain'>
    <tr class="shttitle">
        <td colspan="9" style="font-size: 14px;">
            @php $target = strtotime($year . "-01-01") + (60*60*24*7*($week)); @endphp
            臨時會友崇拜出席紀錄 ({{ date('Y-m-d', $target-(60*60*24*(date('N', $target)-1))) }} - {{ date('Y-m-d', $target+(60*60*24*(7-date('N', $target)))) }})
        </td>
        <td align="right" class="pcode">R1002</td>
    </tr>
    <tr class="tbltitle">
        <td style="font-size: 14px;">崇拜</td>
        <td style="font-size: 14px;">會友編號</td>
        <td style="font-size: 14px;">簽到次數</td>
        <td style="font-size: 14px;">姓名</td>
        <td style="font-size: 14px;">*</td>
        @foreach($worshipList as $worship)
            <td style="font-size: 14px;">{{ $worship->name }}</td>
        @endforeach
    </tr>
    @foreach($data as $member)
    @php
        $bgcolor = "none";
        if ($member->is_new) $bgcolor = "green";
        elseif ($member->has_new_card) $bgcolor = "red";
        elseif ($member->need_form) $bgcolor = "yellow";
    @endphp
    <tr class="tblcontent">
        <td style="font-size: 14px;">{{ $member->worship }}</td>
        <td style="font-size: 14px;">{{ $member->member_code }}</td>
        <td style="font-size: 14px;">{{ $member->sign_in_counts }}</td>
        <td style="font-size: 14px;">{{ $member->name }}</td>
        <td style="font-size:14px;background-color:{{ $bgcolor }};">
            @if($member->is_new) N
            @elseif($member->has_new_card) C
            @elseif($member->need_form) F
            @else &nbsp;
            @endif
        </td>
        @foreach($worshipList as $worship)
            <td@if($member->has_new_card || $member->need_form) style="font-size:14px;background-color:{{ $bgcolor }};"@endif>&nbsp;</td>
        @endforeach
    </tr>
    @endforeach
</table>
<script>
(function () {
    function wrap() {
        var t = document.getElementById('tblMain');
        if (t && !/(^|\s)table-scroll(\s|$)/.test(t.parentNode.className || '')) {
            var w = document.createElement('div');
            w.className = 'table-scroll';
            t.parentNode.insertBefore(w, t);
            w.appendChild(t);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wrap);
    } else {
        wrap();
    }
})();
</script>
</body>
</html>
