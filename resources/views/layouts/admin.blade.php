<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ isset($title) ? $title.' — Quản trị Clare' : 'Quản trị — Clare' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-body">
        <div class="admin-layout">
            <aside class="admin-sidebar">
                <a class="admin-wordmark" href="{{ route('admin.dashboard') }}">CLARE</a>
                <p class="admin-sidebar-label">Quản trị vận hành</p>

                <nav class="admin-navigation" aria-label="Điều hướng quản trị">
                    <a @class(['is-current' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}">Tổng quan</a>
                    <a @class(['is-current' => request()->routeIs('admin.orders.*')]) href="{{ route('admin.orders.index') }}">Đơn hàng</a>
                    <a @class(['is-current' => request()->routeIs('admin.catalog.products.*')]) href="{{ route('admin.catalog.products.index') }}">Sản phẩm</a>
                    <a @class(['is-current' => request()->routeIs('admin.catalog.categories.*')]) href="{{ route('admin.catalog.categories.index') }}">Danh mục</a>
                    <a @class(['is-current' => request()->routeIs('admin.promotions.*')]) href="{{ route('admin.promotions.index') }}">Mã ưu đãi</a>
                    <a @class(['is-current' => request()->routeIs('admin.content.*')]) href="{{ route('admin.content.edit') }}">Nội dung website</a>
                    <a @class(['is-current' => request()->routeIs('admin.users.*')]) href="{{ route('admin.users.index') }}">Tài khoản</a>
                    <a @class(['is-current' => request()->routeIs('admin.appointments.*')]) href="{{ route('admin.appointments.index') }}">Tư vấn &amp; lắp đặt</a>
                </nav>

                <div class="admin-sidebar-footer">
                    <p>{{ auth()->user()->name }}</p>
                    <a href="{{ route('catalog.home') }}">Xem cửa hàng</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit">Đăng xuất</button>
                    </form>
                </div>
            </aside>

            <main class="admin-main">
                @if (session('success') || $errors->any())
                    <div class="admin-notice-stack" aria-live="polite">
                        @if (session('success'))
                            <p class="admin-notice is-success">{{ session('success') }}</p>
                        @endif

                        @if ($errors->any())
                            <div class="admin-notice is-error" role="alert">
                                <p>Không thể cập nhật:</p>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </body>
</html>
