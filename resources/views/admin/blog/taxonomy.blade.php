@extends('layouts.admin', ['title' => 'Danh mục & thẻ bài viết'])
@section('content')
    <header class="admin-page-header"><div><p class="admin-eyebrow">Blog Clare</p><h1>Danh mục &amp; thẻ</h1><p>Tổ chức nội dung để khách tìm bài hướng dẫn và cảm hứng dễ hơn.</p></div><a class="admin-button admin-button-secondary" href="{{ route('admin.blog.posts.index') }}">Bài viết</a></header>
    <div class="admin-two-column">
        <section class="admin-panel">
            <h2>Danh mục</h2>
            <p class="admin-panel-copy">Xóa danh mục chỉ gỡ phân loại; bài viết vẫn được giữ lại.</p>
            <div class="admin-taxonomy-stack">
                @forelse ($categories as $category)
                    <article class="admin-taxonomy-card">
                        <form class="admin-taxonomy-form" method="POST" action="{{ route('admin.blog.taxonomy.categories.update', $category) }}">
                            @csrf @method('PATCH')
                            <label><span>Tên</span><input name="name" value="{{ old('name', $category->name) }}" maxlength="120" required></label>
                            <label><span>Thứ tự</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order) }}" required></label>
                            <label class="admin-taxonomy-description"><span>Mô tả</span><input name="description" value="{{ old('description', $category->description) }}" maxlength="1000"></label>
                            <input name="is_active" type="hidden" value="0"><label class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->is_active))> Đang hiển thị</label>
                            <button class="admin-button admin-button-primary" type="submit">Lưu</button>
                        </form>
                        <div class="admin-taxonomy-meta"><small>{{ $category->posts_count }} bài</small><form method="POST" action="{{ route('admin.blog.taxonomy.categories.destroy', $category) }}" onsubmit="return confirm('Xóa danh mục này? Bài viết sẽ chuyển thành chưa phân loại.')">@csrf @method('DELETE')<button class="admin-button-danger" type="submit">Xóa</button></form></div>
                    </article>
                @empty
                    <p class="admin-empty-cell">Chưa có danh mục bài viết.</p>
                @endforelse
            </div>
        </section>
        <section class="admin-panel">
            <h2>Thẻ</h2>
            <p class="admin-panel-copy">Xóa thẻ chỉ gỡ thẻ khỏi bài viết, không xóa nội dung.</p>
            <div class="admin-taxonomy-stack">
                @forelse ($tags as $tag)
                    <article class="admin-taxonomy-card">
                        <form class="admin-taxonomy-form admin-taxonomy-form-tag" method="POST" action="{{ route('admin.blog.taxonomy.tags.update', $tag) }}">
                            @csrf @method('PATCH')
                            <label><span>Tên thẻ</span><input name="name" value="{{ old('name', $tag->name) }}" maxlength="120" required></label>
                            <button class="admin-button admin-button-primary" type="submit">Lưu</button>
                        </form>
                        <div class="admin-taxonomy-meta"><small>{{ $tag->posts_count }} bài</small><form method="POST" action="{{ route('admin.blog.taxonomy.tags.destroy', $tag) }}" onsubmit="return confirm('Xóa thẻ này khỏi các bài viết?')">@csrf @method('DELETE')<button class="admin-button-danger" type="submit">Xóa</button></form></div>
                    </article>
                @empty
                    <p class="admin-empty-cell">Chưa có thẻ bài viết.</p>
                @endforelse
            </div>
        </section>
    </div>
    <form class="admin-panel admin-inline-form" method="POST" action="{{ route('admin.blog.taxonomy.store') }}">@csrf<label><span>Loại</span><select name="type"><option value="category">Danh mục</option><option value="tag">Thẻ</option></select></label><label class="admin-field-grow"><span>Tên</span><input name="name" maxlength="120" required></label><label class="admin-field-grow"><span>Mô tả danh mục</span><input name="description" maxlength="1000"></label><button class="admin-button admin-button-primary" type="submit">Thêm</button></form>
@endsection
