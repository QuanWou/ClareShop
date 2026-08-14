@extends('layouts.admin', ['title' => 'Tư vấn & lắp đặt'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-appointments-title">
        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Dịch vụ Clare</p>
                <h1 id="admin-appointments-title">Tư vấn &amp;<br>lắp đặt.</h1>
            </div>
            <p>Khách chỉ gửi thời gian mong muốn. Một lịch chỉ được xem là đã chốt sau khi nhân viên xác nhận tại đây.</p>
        </div>

        <form class="admin-filters" method="GET" action="{{ route('admin.appointments.index') }}">
            <label>
                <span>Tìm kiếm</span>
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Mã yêu cầu, tên, email, điện thoại">
            </label>
            <label>
                <span>Loại yêu cầu</span>
                <select name="type">
                    <option value="">Tất cả</option>
                    <option value="consultation" @selected(($filters['type'] ?? null) === 'consultation')>Tư vấn chọn đèn</option>
                    <option value="installation" @selected(($filters['type'] ?? null) === 'installation')>Lắp đặt</option>
                </select>
            </label>
            <label>
                <span>Trạng thái</span>
                <select name="status">
                    <option value="">Tất cả</option>
                    @foreach (['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Lọc</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã yêu cầu</th>
                        <th>Loại</th>
                        <th>Khách hàng</th>
                        <th>Thời gian mong muốn</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td><a class="admin-record-link" href="{{ route('admin.appointments.show', $appointment) }}">{{ $appointment->number }}</a></td>
                            <td>{{ $appointment->typeLabel() }}</td>
                            <td><strong>{{ $appointment->customer_name }}</strong><span>{{ $appointment->customer_phone }}</span></td>
                            <td>{{ $appointment->preferred_starts_at->format('H:i · d/m/Y') }}</td>
                            <td><span class="admin-status admin-status-{{ $appointment->status }}">{{ $appointment->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="5">Không tìm thấy yêu cầu phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($appointments->hasPages())
            <div class="admin-pagination">{{ $appointments->links() }}</div>
        @endif
    </section>
@endsection
