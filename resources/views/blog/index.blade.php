@extends('layouts.storefront', ['title' => 'Cảm hứng', 'description' => 'Câu chuyện, hướng dẫn chọn đèn và cảm hứng không gian từ Clare.', 'bodyClass' => 'blog-index-page'])
@section('content')
    <section class="blog-hero"><div class="shell"><p class="eyebrow">Tạp chí Clare</p><h1>Chuyện về ánh sáng<br><em>và những căn phòng.</em></h1><p>Các gợi ý thực tế để chọn, đặt và sống cùng ánh sáng dịu hơn mỗi ngày.</p></div></section>
    <section class="section"><div class="shell">
        <nav class="blog-category-nav" aria-label="Danh mục bài viết"><a @class(['is-current' => !$selectedCategory]) href="{{ route('blog.index') }}">Tất cả</a>@foreach ($categories as $category)<a @class(['is-current' => $selectedCategory?->is($category)]) href="{{ route('blog.index', ['category' => $category->slug]) }}">{{ $category->name }} <span>{{ $category->posts_count }}</span></a>@endforeach</nav>
        <div class="blog-card-grid" data-reveal-group>
            @forelse ($posts as $post)<article class="blog-card" data-reveal-item><a class="blog-card-image" href="{{ route('blog.show', $post) }}">@if ($post->featured_image_url)<img src="{{ $post->featured_image_url }}" alt="{{ $post->featured_image_alt ?: $post->title }}" loading="lazy">@else<span class="image-placeholder"></span>@endif</a><div><p class="eyebrow">{{ $post->category?->name ?? 'Chuyện Clare' }} · {{ $post->published_at?->format('d/m/Y') }}</p><h2><a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a></h2><p>{{ $post->excerpt }}</p><a class="text-link" href="{{ route('blog.show', $post) }}">Đọc bài <span aria-hidden="true">→</span></a></div></article>
            @empty <div class="catalog-empty-state"><h2>Chưa có bài viết trong mục này.</h2><p>Clare đang chuẩn bị những câu chuyện mới.</p></div>@endforelse
        </div>
        {{ $posts->links() }}
    </div></section>
@endsection
