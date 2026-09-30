<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $nextCode = DB::table('tbl_member')->where('code', '<', 9000)->max('code') + 1;

        return view('members.form', [
            'member' => null,
            'nextCode' => $nextCode,
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

        $validated['state'] = $request->input('state', Member::STATE_ACTIVE);
        $validated['create_date'] = now();
        $validated['creator_id'] = auth()->id();

        $member = Member::create($validated);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $request->file('photo')->storeAs('public/file/member', $member->code . '.jpg');
        }

        return redirect()->route('members.show', $member->id)->with('success', '會友已新增');
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

        $validated['modifier_id'] = auth()->id();

        $member->update($validated);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $request->file('photo')->storeAs('public/file/member', $member->code . '.jpg');
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
