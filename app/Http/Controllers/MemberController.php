<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::active()->orderBy('code');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('english_name', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate(30);

        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.form', [
            'member' => null,
            // 供表單提示「留空會自動編號」
            'nextNewFriend' => Member::nextCodeFor(Member::ACCOUNT_TYPE_NEW_MEMBER),
            'nextMember'    => Member::nextCodeFor(Member::ACCOUNT_TYPE_MEMBER),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'english_name' => 'nullable|string|max:255',
            'gender' => 'nullable|integer',
            'birthday' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'account_type' => 'nullable|integer',
            'believe' => 'nullable|string|max:255',
            'believe_date' => 'nullable|date',
            'baptized' => 'nullable|string|max:255',
            'baptized_date' => 'nullable|date',
            'arrived_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'address_district' => 'nullable|string|max:255',
            'address_estate' => 'nullable|string|max:255',
            'address_house' => 'nullable|string|max:255',
            'address_flat' => 'nullable|string|max:255',
            'contact_home' => 'nullable|string|max:255',
            'contact_mobile' => 'nullable|string|max:255',
            'contact_office' => 'nullable|string|max:255',
            'contact_others' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
        ]);

        // tbl_member 在生產是 strict mode（STRICT_TRANS_TABLES），而 code / gender /
        // account_type / new_card / arrived_date / creator_id / modifier_id 都是
        // NOT NULL 且無預設值。空白「編號」會被 ConvertEmptyStringsToNull 轉成 NULL，
        // 直接寫入會觸發 SQLSTATE 23000（Column 'code' cannot be null）——
        // 新朋友通常沒有編號，因此每次新增都失敗（2026-11-07 生產事故）。
        // 因此：編號留空 = 由系統自動分配（新朋友 9xxx 起、會友／同工 1001 起），
        // 有手動輸入則以手動為準。
        $validated['account_type'] = $validated['account_type'] ?? Member::ACCOUNT_TYPE_MEMBER;

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code === '') {
            $code = Member::nextCodeFor((int) $validated['account_type']);
        } elseif (Member::active()->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "會友編號 {$code} 已有人使用，請確認；或留空讓系統自動編號。",
            ]);
        }

        $validated['code'] = $code;
        $validated['gender'] = $validated['gender'] ?? Member::GENDER_UNKNOWN;
        $validated['new_card'] = 0;
        $validated['arrived_date'] = $validated['arrived_date'] ?? today()->toDateString();
        $validated['state'] = $request->input('state', Member::STATE_ACTIVE);
        $validated['create_date'] = now();
        $validated['creator_id'] = auth()->id() ?? 0;
        $validated['modifier_id'] = auth()->id() ?? 0;

        $member = Member::create($validated);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photoName = ($member->code !== '' ? $member->code : 'member-' . $member->id) . '.jpg';
            $request->file('photo')->storeAs('public/file/member', $photoName);
        }

        return redirect()->route('members.show', $member->id)
            ->with('success', "會友已新增（編號 {$member->code}）");
    }

    public function show(Member $member)
    {
        $worshipStats = [
            'last_date' => $member->worshipAttendances()->max('attendance_date'),
            'two_month_count' => $member->worshipAttendances()->where('attendance_date', '>=', now()->subMonths(2))->count(),
            'six_month_count' => $member->worshipAttendances()->where('attendance_date', '>=', now()->subMonths(6))->count(),
            'year_count' => $member->worshipAttendances()->where('attendance_date', '>=', now()->subMonths(12))->count(),
        ];

        return view('members.show', compact('member', 'worshipStats'));
    }

    public function edit(Member $member)
    {
        return view('members.form', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'english_name' => 'nullable|string|max:255',
            'gender' => 'nullable|integer',
            'birthday' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'account_type' => 'nullable|integer',
            'believe' => 'nullable|string|max:255',
            'believe_date' => 'nullable|date',
            'baptized' => 'nullable|string|max:255',
            'baptized_date' => 'nullable|date',
            'arrived_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'address_district' => 'nullable|string|max:255',
            'address_estate' => 'nullable|string|max:255',
            'address_house' => 'nullable|string|max:255',
            'address_flat' => 'nullable|string|max:255',
            'contact_home' => 'nullable|string|max:255',
            'contact_mobile' => 'nullable|string|max:255',
            'contact_office' => 'nullable|string|max:255',
            'contact_others' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
        ]);

        $validated['modifier_id'] = auth()->id() ?? 0;

        // 同 store()：code 與 arrived_date 在生產是 NOT NULL，不可寫入 NULL
        if (array_key_exists('code', $validated)) {
            $code = trim((string) $validated['code']);
            if ($code !== ''
                && Member::active()->where('code', $code)->where('id', '!=', $member->id)->exists()) {
                throw ValidationException::withMessages([
                    'code' => "會友編號 {$code} 已有人使用，請確認；或留空清除編號。",
                ]);
            }
            $validated['code'] = $code;
        }
        if (array_key_exists('arrived_date', $validated) && $validated['arrived_date'] === null) {
            unset($validated['arrived_date']);   // 保留原有到訪日期
        }

        $member->update($validated);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photoName = ($member->code !== '' ? $member->code : 'member-' . $member->id) . '.jpg';
            $request->file('photo')->storeAs('public/file/member', $photoName);
        }

        return redirect()->route('members.show', $member->id)->with('success', '會友已更新');
    }

    public function destroy(Member $member)
    {
        $member->update([
            'state' => Member::STATE_DELETED,
            'code' => '',
            'account_type' => -1,
        ]);

        return redirect()->route('members.index')->with('success', '會友已刪除');
    }

    public function duplicate()
    {
        $duplicateNames = Member::active()
            ->select('name', DB::raw('COUNT(*) as cnt'))
            ->where('account_type', Member::ACCOUNT_TYPE_NEW_MEMBER)
            ->groupBy('name')
            ->having('cnt', '>', 1)
            ->pluck('name');

        $duplicateList = [];
        foreach ($duplicateNames as $name) {
            $duplicateList[] = Member::where('name', $name)
                ->where('account_type', Member::ACCOUNT_TYPE_NEW_MEMBER)
                ->orderBy('code')
                ->get();
        }

        return view('members.duplicate', compact('duplicateList'));
    }

    public function updateAccount()
    {
        $user = auth()->user();
        return view('auth.update-account', compact('user'));
    }

    public function autocomplete(Request $request)
    {
        $term = $request->get('q', '');
        $members = Member::active()
            ->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'code', 'name']);

        return response()->json($members->map(fn($m) => [
            'id' => $m->id,
            'code' => $m->code,
            'label' => "{$m->name} {$m->code}",
            'value' => $m->code,
        ]));
    }
}
