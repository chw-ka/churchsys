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
        <td colspan="4">原始數據 ({{ $year }})</td>
        <td align="right" class="pcode">R1007</td>
    </tr>
    <tr class="tbltitle">
        <td>Code</td>
        <td>Name</td>
        <td>WeekNo</td>
        <td>Date</td>
    </tr>
    @foreach($data as $row)
    <tr class="tblcontent">
        <td>{{ $row->code }}</td>
        <td>{{ $row->name }}</td>
        <td>{{ $row->weekno }}</td>
        <td>{{ $row->attendance_date }}</td>
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
