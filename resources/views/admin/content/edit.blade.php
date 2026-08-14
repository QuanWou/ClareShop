@extends('layouts.admin', ['title' => 'Nội dung website'])

@php
    $fieldCount = collect($contentGroups)->sum(fn ($group) => count($group['fields']));
@endphp

@section('content')
    <section class="admin-page admin-content-page" aria-labelledby="admin-content-title">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Content / Storefront</p>
                <h1 id="admin-content-title">Nội dung website.</h1>
            </div>
            <p>Sửa nội dung thương hiệu và các khối biên tập tại một nơi. Thay đổi được áp dụng cho storefront ngay sau khi lưu.</p>
        </div>

        <div class="admin-content-scope">
            <div>
                <strong>{{ $fieldCount }}</strong>
                <span>trường nội dung</span>
            </div>
            <p>Tên, mô tả, ảnh, giá và tồn kho của từng sản phẩm tiếp tục được quản lý trong <a href="{{ route('admin.catalog.products.index') }}">Sản phẩm</a> và <a href="{{ route('admin.catalog.categories.index') }}">Danh mục</a>.</p>
            <a class="admin-primary-link" href="{{ route('catalog.home') }}" target="_blank" rel="noreferrer">Mở storefront</a>
        </div>

        <nav class="admin-content-navigation" aria-label="Đi đến nhóm nội dung">
            @foreach ($contentGroups as $groupKey => $group)
                <a href="#content-{{ $groupKey }}">{{ $group['label'] }}</a>
            @endforeach
        </nav>

        <form class="admin-content-form" method="POST" action="{{ route('admin.content.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            @foreach ($contentGroups as $groupKey => $group)
                <section class="admin-panel admin-content-group" id="content-{{ $groupKey }}" aria-labelledby="content-{{ $groupKey }}-title">
                    <div class="admin-panel-heading">
                        <div>
                            <p class="admin-eyebrow">Nhóm {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                            <h2 id="content-{{ $groupKey }}-title">{{ $group['label'] }}</h2>
                        </div>
                        <p>{{ $group['description'] }}</p>
                    </div>

                    <div class="admin-content-fields">
                        @foreach ($group['fields'] as $field)
                            @php
                                $fieldId = 'site-content-'.$field['key'];
                                $fieldError = $field['type'] === 'image' ? 'images.'.$field['key'] : 'content.'.$field['key'];
                            @endphp

                            @if ($field['type'] === 'image')
                                <div @class(['admin-content-field', 'admin-content-image-field', 'has-error' => $errors->has($fieldError)])>
                                    <div class="admin-content-image-preview">
                                        <img src="{{ asset($field['value']) }}" alt="" width="360" height="240">
                                    </div>
                                    <label for="{{ $fieldId }}">
                                        <span>{{ $field['label'] }}</span>
                                        <input id="{{ $fieldId }}" name="images[{{ $field['key'] }}]" type="file" accept="image/jpeg,image/png,image/webp">
                                        <small>JPG, PNG hoặc WebP, tối đa 5 MB. Bỏ trống để giữ ảnh hiện tại.</small>
                                    </label>
                                    @error($fieldError)<p class="admin-content-error">{{ $message }}</p>@enderror
                                </div>
                            @else
                                <label @class(['admin-content-field', 'admin-content-textarea-field' => $field['type'] === 'textarea', 'has-error' => $errors->has($fieldError)]) for="{{ $fieldId }}">
                                    <span>{{ $field['label'] }}</span>
                                    @if ($field['type'] === 'textarea')
                                        <textarea id="{{ $fieldId }}" name="content[{{ $field['key'] }}]" rows="4" maxlength="{{ $field['max'] }}" required>{{ old('content.'.$field['key'], $field['value']) }}</textarea>
                                    @else
                                        <input id="{{ $fieldId }}" name="content[{{ $field['key'] }}]" type="{{ $field['type'] === 'email' ? 'email' : 'text' }}" value="{{ old('content.'.$field['key'], $field['value']) }}" maxlength="{{ $field['max'] }}" required>
                                    @endif
                                    <small>Tối đa {{ $field['max'] }} ký tự.</small>
                                    @error($fieldError)<span class="admin-content-error">{{ $message }}</span>@enderror
                                </label>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="admin-content-savebar">
                <p><strong>Sẵn sàng cập nhật?</strong><span>Kiểm tra lại nội dung và ảnh trước khi lưu.</span></p>
                <button class="admin-form-submit" type="submit">Lưu toàn bộ nội dung</button>
            </div>
        </form>
    </section>
@endsection
