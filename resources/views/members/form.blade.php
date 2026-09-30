@extends('layouts.app')

@section('title', isset($member) ? '編輯會友' : '新增會友')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}">首頁</a> &gt;
<a href="{{ route('members.index') }}">會友</a> &gt;
<span>{{ isset($member) ? '編輯: ' . $member->name : '新增會友' }}</span>
@endsection

@section('content')
<h1>{{ isset($member) ? '編輯會友: ' . $member->name : '新增會友' }}</h1>

<div class="form">
    <p class="note"><span class="required">*</span>必須填寫.</p>

    @if ($errors->any())
    <div class="flash-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php
        $photoPath = isset($member) ? storage_path('app/public/file/member/' . $member->code . '.jpg') : null;
        $image = ($photoPath && file_exists($photoPath)) ? asset('storage/file/member/' . $member->code . '.jpg') : asset('images/anonymous.gif');
    @endphp

    <img src="{{ $image }}" width="200">

    <form method="POST" action="{{ isset($member) ? route('members.update', $member->id) : route('members.store') }}" enctype="multipart/form-data">
        @csrf
        @if(isset($member))
            @method('PUT')
        @endif

        <fieldset>
            <legend>個人資料</legend>

            <div class="row">
                <label>會友類別</label>
                <select name="account_type">
                    <option value="0" {{ (old('account_type', $member->account_type ?? 0) == 0) ? 'selected' : '' }}>新朋友</option>
                    <option value="1" {{ (old('account_type', $member->account_type ?? 1) == 1) ? 'selected' : '' }}>會友</option>
                    <option value="2" {{ (old('account_type', $member->account_type ?? '') == 2) ? 'selected' : '' }}>同工</option>
                </select>
                <input type="hidden" name="state" value="{{ $member->state ?? 1 }}">
            </div>

            <div class="row">
                <label>編號</label>
                <input type="text" name="code" value="{{ old('code', $member->code ?? '') }}" size="10" maxlength="10">
            </div>

            <div class="row">
                <label>姓名 <span class="required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $member->name ?? '') }}" size="60" maxlength="255" required>
            </div>

            <div class="row">
                <label>英文名</label>
                <input type="text" name="english_name" value="{{ old('english_name', $member->english_name ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>備註</label>
                <textarea name="remarks" rows="6" cols="50">{{ old('remarks', $member->remarks ?? '') }}</textarea>
            </div>

            <div class="row">
                <label>性別</label>
                <select name="gender">
                    <option value="1" {{ (old('gender', $member->gender ?? 3) == 1) ? 'selected' : '' }}>女</option>
                    <option value="2" {{ (old('gender', $member->gender ?? 3) == 2) ? 'selected' : '' }}>男</option>
                    <option value="3" {{ (old('gender', $member->gender ?? 3) == 3) ? 'selected' : '' }}>未知</option>
                </select>
            </div>

            <div class="row">
                <label>生日</label>
                <input type="date" name="birthday" value="{{ old('birthday', $member->birthday ?? '') }}">
            </div>

            <div class="row">
                <label>電郵</label>
                <input type="email" name="email" value="{{ old('email', $member->email ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>照片</label>
                <input type="file" name="photo" accept="image/*">
            </div>
        </fieldset>

        <fieldset>
            <legend>信仰狀況</legend>

            <div class="row">
                <label>信主</label>
                <input type="text" name="believe" value="{{ old('believe', $member->believe ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>信主日期</label>
                <input type="date" name="believe_date" value="{{ old('believe_date', $member->believe_date ?? '') }}">
            </div>

            <div class="row">
                <label>受浸</label>
                <input type="text" name="baptized" value="{{ old('baptized', $member->baptized ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>受浸日期</label>
                <input type="date" name="baptized_date" value="{{ old('baptized_date', $member->baptized_date ?? '') }}">
            </div>

            <div class="row">
                <label>到訪日期</label>
                <input type="date" name="arrived_date" value="{{ old('arrived_date', $member->arrived_date ?? '') }}">
            </div>
        </fieldset>

        <fieldset>
            <legend>聯絡資料</legend>

            <div class="row">
                <label>地區</label>
                <input type="text" name="address_district" value="{{ old('address_district', $member->address_district ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>屋苑</label>
                <input type="text" name="address_estate" value="{{ old('address_estate', $member->address_estate ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>樓宇</label>
                <input type="text" name="address_house" value="{{ old('address_house', $member->address_house ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>單位</label>
                <input type="text" name="address_flat" value="{{ old('address_flat', $member->address_flat ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>住宅電話</label>
                <input type="text" name="contact_home" value="{{ old('contact_home', $member->contact_home ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>手提電話</label>
                <input type="text" name="contact_mobile" value="{{ old('contact_mobile', $member->contact_mobile ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>辦公電話</label>
                <input type="text" name="contact_office" value="{{ old('contact_office', $member->contact_office ?? '') }}" size="60" maxlength="255">
            </div>

            <div class="row">
                <label>其他聯絡</label>
                <input type="text" name="contact_others" value="{{ old('contact_others', $member->contact_others ?? '') }}" size="60" maxlength="255">
            </div>
        </fieldset>

        <div class="row buttons" style="padding: 10px 0;">
            <input type="submit" value="{{ isset($member) ? '儲存' : '新增' }}">
        </div>
    </form>
</div>
@endsection
