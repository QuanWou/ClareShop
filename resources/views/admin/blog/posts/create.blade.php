@extends('layouts.admin', ['title' => 'Viết bài'])
@section('content')
    <header class="admin-page-header"><div><p class="admin-eyebrow">Blog Clare</p><h1>Viết bài mới</h1></div><a class="admin-button admin-button-secondary" href="{{ route('admin.blog.posts.index') }}">Quay lại</a></header>
    <form class="admin-panel admin-form" action="{{ route('admin.blog.posts.store') }}" method="POST" enctype="multipart/form-data">@csrf @include('admin.blog.posts.partials.form', ['post' => null])<button class="admin-button admin-button-primary" type="submit">Tạo bài viết</button></form>
@endsection
