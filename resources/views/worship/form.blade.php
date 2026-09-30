@extends('layouts.app')

@section('title', isset($worship) ? '編輯崇拜' : '新增崇拜')

@section('content')
<h1>{{ isset($worship) ? '編輯崇拜: ' . $worship->name : '新增崇拜' }}</h1>

<div class="form">
    @if ($errors->any())
    <div class="flash-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ isset($worship) ? route('worship.update', $worship->id) : route('worship.store') }}">
        @csrf
        @if(isset($worship))
            @method('PUT')
        @endif

        <fieldset>
            <div class="row">
                <label>名稱 <span class="required">*</span></label>
                <input type="text" name="name" value="{{ old('name', $worship->name ?? '') }}" size="60" required>
            </div>

            <div class="row">
                <label>每週</label>
                <select name="weekly">
                    @foreach($weeklyList as $key => $label)
                        <option value="{{ $key }}" {{ (old('weekly', $worship->weekly ?? 0) == $key) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <label>開始時間</label>
                <input type="text" name="start_time" value="{{ old('start_time', $worship->start_time ?? '') }}" placeholder="例如: 09:00">
            </div>

            <div class="row">
                <label>結束時間</label>
                <input type="text" name="end_time" value="{{ old('end_time', $worship->end_time ?? '') }}" placeholder="例如: 11:00">
            </div>

            <div class="row">
                <label>狀態</label>
                <select name="state">
                    <option value="1" {{ (old('state', $worship->state ?? 1) == 1) ? 'selected' : '' }}>活躍</option>
                    <option value="0" {{ (old('state', $worship->state ?? 1) == 0) ? 'selected' : '' }}>停用</option>
                </select>
            </div>

            <div class="row">
                <label>備註</label>
                <textarea name="remarks" rows="3" cols="50">{{ old('remarks', $worship->remarks ?? '') }}</textarea>
            </div>
        </fieldset>

        <div class="row buttons" style="padding: 10px 0;">
            <input type="submit" value="{{ isset($worship) ? '更新' : '新增' }}">
        </div>
    </form>
</div>
@endsection
