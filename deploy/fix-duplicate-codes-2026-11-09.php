<?php

/**
 * One-off data fix: 清理 tbl_member 重複編號（2026-11-09，只執行一次）
 *
 * 完整明細（清理前）見 .workbuddy/archive/duplicate-codes-2026-11-09.tsv
 * （70 組、145 筆）。規則已與系統擁有者確認：
 *
 *   A 類 — 同名同姓、相鄰 id 的重複登記（同一人被登記多次）：
 *           保留出席較多的一筆（相同時取 id 較細）；
 *           對方的出席紀錄併入保留者（同日同崇拜已有紀錄則剔除，
 *           避免同日重複計算），然後軟刪除（state=0、code=''、account_type=-1）。
 *
 *   B 類 — 兩個不同人共用同一編號（姓名不同）：
 *           給出席較少的一方（相同時取 id 較大）重新編配新的 9xxx 號碼，
 *           另一方保留原號碼。
 *
 * 安全措施：
 *   - 全程包在同一個 transaction 內，任何錯誤都會回滾。
 *   - 逐組印出處理明細供覆核。
 *   - 執行前已有備份：backups/pre-deploy-20261108_114949.sql.gz
 *     （及執行當日另行備份）。
 *
 * 執行方式（生產容器內）：
 *   # 先試跑（全程在 transaction 內，最後回滾，不寫入任何資料）
 *   docker compose exec -T app php /var/www/html/deploy/fix-duplicate-codes-2026-11-09.php --dry-run
 *   # 核對輸出無誤後正式執行
 *   docker compose exec -T app php /var/www/html/deploy/fix-duplicate-codes-2026-11-09.php
 *
 * 執行後緊接著加唯一約束：
 *   docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys \
 *     < deploy/sql/03_member_code_unique.sql
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Member;
use App\Models\WorshipAttendance;
use Illuminate\Support\Facades\DB;

// 冪等保護：如果已經沒有重複（或 code_uq 已存在），直接離開
$dupCodes = DB::table('tbl_member')
    ->select('code')
    ->where('code', '!=', '')
    ->where('state', Member::STATE_ACTIVE)
    ->groupBy('code')
    ->havingRaw('COUNT(*) > 1')
    ->orderByRaw('CAST(code AS UNSIGNED)')
    ->pluck('code');

echo "發現重複編號組數: " . $dupCodes->count() . "\n";
if ($dupCodes->isEmpty()) {
    echo "沒有需要清理的重複編號，結束。\n";
    exit(0);
}

$stats = ['merged' => 0, 'renumbered' => 0, 'moved_att' => 0, 'dropped_att' => 0];
// B 類重新編號從 9xxx 順序續排（11293 起，隨實際資料而變）
$nextNewFriend = (int) Member::nextCodeFor(Member::ACCOUNT_TYPE_NEW_MEMBER);

// --dry-run：外層 transaction 最後回滾，內層 DB::transaction 只是 savepoint
$dryRun = in_array('--dry-run', $argv ?? [], true);
if ($dryRun) {
    echo "=== DRY RUN：所有變更最後會回滾，不會寫入 ===\n";
}
DB::beginTransaction();

DB::transaction(function () use ($dupCodes, &$stats, &$nextNewFriend) {
    foreach ($dupCodes as $code) {
        $rows = Member::active()
            ->withCount('worshipAttendances')
            ->where('code', $code)
            ->orderBy('id')
            ->get();

        if ($rows->count() < 2) {
            continue; // transaction 中前面的組可能已處理掉
        }

        $names = $rows->pluck('name')->map(fn ($n) => trim((string) $n))->unique();

        if ($names->count() === 1) {
            // ── A 類：同一人重複登記 ──
            $keeper = $rows->sortBy([
                ['worship_attendances_count', 'desc'],
                ['id', 'asc'],
            ])->first();
            echo "[A] code={$code} 保留 #{$keeper->id} {$keeper->name}"
                . "（出席 {$keeper->worship_attendances_count}），其餘 "
                . ($rows->count() - 1) . " 筆併入後軟刪除\n";

            foreach ($rows as $row) {
                if ($row->id === $keeper->id) {
                    continue;
                }

                // 併入出席紀錄（同日同崇拜已有 → 剔除，避免同日重複計算）
                $atts = WorshipAttendance::where('member_id', $row->id)->get();
                foreach ($atts as $att) {
                    $day = $att->attendance_date->toDateString();
                    $existing = WorshipAttendance::where('member_id', $keeper->id)
                        ->where('worship_id', $att->worship_id)
                        ->whereDate('attendance_date', $day)
                        ->count();

                    if ($existing > 0) {
                        WorshipAttendance::where('member_id', $row->id)
                            ->where('worship_id', $att->worship_id)
                            ->whereDate('attendance_date', $day)
                            ->delete();
                        $stats['dropped_att']++;
                        echo "    剔除重複出席 #{$row->id} → {$day} 崇拜{$att->worship_id}（保留者當日已有）\n";
                    } else {
                        WorshipAttendance::where('member_id', $row->id)
                            ->where('worship_id', $att->worship_id)
                            ->whereDate('attendance_date', $day)
                            ->update(['member_id' => $keeper->id]);
                        $stats['moved_att']++;
                    }
                }

                // 軟刪除（與 MemberController::destroy 同一慣例；script 無登入者，modifier_id 記 0）
                $row->update([
                    'state'        => Member::STATE_DELETED,
                    'code'         => '',
                    'account_type' => -1,
                    'modifier_id'  => 0,
                ]);
                $stats['merged']++;
            }
        } else {
            // ── B 類：兩個不同人共用編號 ──
            $renum = $rows->sortBy([
                ['worship_attendances_count', 'asc'],
                ['id', 'desc'],
            ])->first();

            echo "[B] code={$code} #{$renum->id} {$renum->name}"
                . "（出席 {$renum->worship_attendances_count}）重新編號 → {$nextNewFriend}\n";
            $renum->update([
                'code'        => (string) $nextNewFriend,
                'modifier_id' => 0,
            ]);
            $nextNewFriend++;
            $stats['renumbered']++;
        }
    }
});

if ($dryRun) {
    DB::rollBack();
    echo "\n=== DRY RUN 完成：已回滾，未寫入任何資料 ===\n";
} else {
    DB::commit();
    echo "\n=== 已提交 ===\n";
}

echo "A 類軟刪除（重複登記）: {$stats['merged']} 筆\n";
echo "B 類重新編號:          {$stats['renumbered']} 筆\n";
echo "出席紀錄併入:          {$stats['moved_att']} 筆\n";
echo "出席紀錄剔除（同日重複）: {$stats['dropped_att']} 筆\n";

if ($dryRun) {
    echo "\n（DRY RUN 期間的驗證結果不適用，正式執行後會再驗證一次）\n";
    exit(0);
}

// 最終驗證
$left = DB::table('tbl_member')
    ->where('code', '!=', '')
    ->where('state', Member::STATE_ACTIVE)
    ->groupBy('code')
    ->havingRaw('COUNT(*) > 1')
    ->count();
echo $left === 0
    ? "驗證通過：已無重複編號。可執行 deploy/sql/03_member_code_unique.sql 加唯一約束。\n"
    : "警告：仍有 {$left} 組重複，請檢查！不要執行唯一約束。\n";
