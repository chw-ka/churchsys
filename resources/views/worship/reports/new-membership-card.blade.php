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
        <td colspan="4">
            @php $target = strtotime($year . "-01-01") + (60*60*24*7*($week)); @endphp
            以下肢體 "個人名牌"已備妥 ({{ date('Y-m-d', $target-(60*60*24*(date('N', $target)-1))) }} - {{ date('Y-m-d', $target+(60*60*24*(7-date('N', $target)))) }})
        </td>
        <td align="right" class="pcode">R1001</td>
    </tr>
    <tr class="tbltitle">
        <td>時段</td>
        <td>小組</td>
        <td>會友編號</td>
        <td>姓名</td>
        <td>相片編號</td>
    </tr>
    @foreach($data as $member)
    <tr class="tblcontent">
        <td>{{ $member->period ?: '未入組' }}</td>
        <td>{{ $member->small_group ?: '未入組' }}</td>
        <td>{{ $member->member_code }}</td>
        <td>{{ $member->name }}</td>
        <td>&nbsp;</td>
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
