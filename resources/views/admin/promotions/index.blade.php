@extends('layouts.admin', ['title' => 'Mã ưu đãi'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-promotions-title">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Khuyến mãi</p>
                <h1 id="admin-promotions-title">Mã ưu đãi</h1>
            </div>
            <p>Tạo, kiểm soát thời hạn và dừng mã. Dữ liệu ưu đãi của đơn đã tạo luôn được lưu thành snapshot riêng.</p>
        </div>

        <div class="admin-promotions-toolbar">
            <p>{{ $promotions->total() }} mã trong hệ thống</p>
            <a class="admin-primary-link" href="{{ route('admin.promotions.create') }}">Tạo mã ưu đãi</a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã / chương trình</th>
                        <th>Mức giảm</th>
                        <th>Điều kiện</th>
                        <th>Hiệu lực</th>
                        <th>Lượt dùng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        <tr>
                            <td>
                                <strong>{{ $promotion->code }}</strong>
                                <span>{{ $promotion->name }}</span>
                            </td>
                            <td>
                                {{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format($promotion->discount_value, 2, '.', ''), '0'), '.') . '%' : \App\Modules\Shared\Support\Money::formatVnd($promotion->discount_value) }}
                            </td>
                            <td>
                                <span>Từ {{ $promotion->minimum_order_amount ? \App\Modules\Shared\Support\Money::formatVnd($promotion->minimum_order_amount) : 'mọi đơn' }}</span>
                                @if ($promotion->maximum_discount_amount)
                                    <span>Tối đa {{ \App\Modules\Shared\Support\Money::formatVnd($promotion->maximum_discount_amount) }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $promotion->starts_at?->format('d/m/Y') ?? 'Ngay' }}
                                <span>đến {{ $promotion->ends_at?->format('d/m/Y') ?? 'Không giới hạn' }}</span>
                            </td>
                            <td>
                                {{ $promotion->usage_count }}
                                @if ($promotion->usage_limit)
                                    <span>/ {{ $promotion->usage_limit }}</span>
                                @else
                                    <span>/ không giới hạn</span>
                                @endif
                            </td>
                            <td>
                                <span class="admin-status {{ $promotion->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">
                                    {{ $promotion->is_active ? 'Đang bật' : 'Đã tắt' }}
                                </span>
                            </td>
                            <td>
                                <a class="admin-record-link" href="{{ route('admin.promotions.edit', $promotion) }}">Chỉnh sửa</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="7">Chưa có mã ưu đãi nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">{{ $promotions->links() }}</div>
    </section>
@endsection
