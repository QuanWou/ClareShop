@extends('layouts.admin', ['title' => 'Đánh giá sản phẩm'])

@section('content')
    <header class="admin-page-header">
        <div><p class="admin-eyebrow">Uy tín cửa hàng</p><h1>Đánh giá sản phẩm</h1><p>Duyệt đánh giá từ khách đã mua, ẩn nội dung không phù hợp và giữ ghi chú nội bộ.</p></div>
    </header>

    <form class="admin-filter-bar" method="GET">
        <label><span>Trạng thái</span><select name="status"><option value="">Tất cả</option><option value="pending" @selected($statusFilter === 'pending')>Chờ duyệt</option><option value="approved" @selected($statusFilter === 'approved')>Đang hiển thị</option><option value="hidden" @selected($statusFilter === 'hidden')>Đã ẩn</option></select></label>
        <button class="admin-button admin-button-secondary" type="submit">Lọc</button>
    </form>

    <div class="admin-review-list">
        @forelse ($reviews as $review)
            <article class="admin-panel admin-review-card">
                <header><div><p class="review-stars">{{ str_repeat('★', $review->rating).str_repeat('☆', 5 - $review->rating) }}</p><h2>{{ $review->title ?: $review->product->name }}</h2><p>{{ $review->user->name }} · {{ $review->user->email }} · đơn {{ $review->order?->number }}</p></div><span class="admin-status-badge">{{ $review->statusLabel() }}</span></header>
                <p>{{ $review->comment }}</p>
                @if ($review->images->isNotEmpty())<div class="admin-review-images">@foreach ($review->images as $image)<img src="{{ $image->url }}" alt="" loading="lazy">@endforeach</div>@endif
                <form class="admin-inline-form" action="{{ route('admin.reviews.update', $review) }}" method="POST">
                    @csrf @method('PATCH')
                    <label><span>Trạng thái</span><select name="status"><option value="pending" @selected($review->status === 'pending')>Chờ duyệt</option><option value="approved" @selected($review->status === 'approved')>Hiển thị</option><option value="hidden" @selected($review->status === 'hidden')>Ẩn</option></select></label>
                    <label class="admin-field-grow"><span>Ghi chú kiểm duyệt</span><input name="moderation_note" value="{{ $review->moderation_note }}" maxlength="1000"></label>
                    <button class="admin-button admin-button-primary" type="submit">Lưu</button>
                </form>
            </article>
        @empty
            <div class="admin-empty-state"><h2>Không có đánh giá phù hợp.</h2></div>
        @endforelse
    </div>
    {{ $reviews->links() }}
@endsection
