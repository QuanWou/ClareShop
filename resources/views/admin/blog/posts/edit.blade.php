@extends('layouts.admin', ['title' => 'Sửa bài viết'])
@section('content')
    <header class="admin-page-header"><div><p class="admin-eyebrow">Blog Clare</p><h1>{{ $post->title }}</h1></div>@if ($post->status === 'published')<a class="admin-button admin-button-secondary" href="{{ route('blog.show', $post) }}" target="_blank">Xem bài</a>@endif</header>
    <form class="admin-panel admin-form" action="{{ route('admin.blog.posts.update', $post) }}" method="POST" enctype="multipart/form-data">@csrf @method('PATCH') @include('admin.blog.posts.partials.form')<button class="admin-button admin-button-primary" type="submit">Lưu thay đổi</button></form>
    <form action="{{ route('admin.blog.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Lưu trữ bài viết này?')">@csrf @method('DELETE')<button class="admin-button admin-button-danger" type="submit">Lưu trữ bài viết</button></form>
@endsection
