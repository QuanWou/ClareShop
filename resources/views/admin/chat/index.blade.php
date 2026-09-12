@extends('layouts.admin')

@section('content')
    <section
        class="admin-chat-page"
        data-admin-chat
        data-list-url="{{ route('admin.chat.conversations') }}"
        data-show-url="{{ route('admin.chat.show', ['conversation' => '__ID__']) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <header class="admin-chat-toolbar">
            <div>
                <p class="eyebrow">Hỗ trợ khách hàng</p>
                <h1>Chat hỗ trợ</h1>
            </div>

            <details class="admin-chat-settings">
                <summary>Cấu hình chatbot</summary>
                <form class="admin-chat-settings-panel admin-chat-settings-grid" method="POST" action="{{ route('admin.chat.settings.update') }}">
                    @csrf
                    @method('PATCH')
                    <label>
                        Tên trợ lý
                        <input name="assistant_name" type="text" value="{{ old('assistant_name', $settings->get('assistant_name')) }}" required maxlength="80">
                    </label>
                    <div class="admin-chat-settings-toggles">
                        <label><input name="widget_enabled" type="checkbox" value="1" @checked($settings->enabled('widget_enabled'))> Hiện widget</label>
                        <label><input name="gemini_enabled" type="checkbox" value="1" @checked($settings->enabled('gemini_enabled'))> Bật Gemini</label>
                        <label><input name="external_questions_enabled" type="checkbox" value="1" @checked($settings->enabled('external_questions_enabled'))> Câu hỏi bên ngoài</label>
                    </div>
                    @foreach ([
                        'welcome_message' => 'Tin nhắn chào',
                        'handoff_message' => 'Tin nhắn chuyển nhân viên',
                        'gemini_unavailable_message' => 'Khi Gemini bận',
                        'guest_order_message' => 'Khách chưa đăng nhập hỏi đơn',
                        'closed_message' => 'Hội thoại đã đóng',
                        'admin_unavailable_message' => 'Nhân viên chưa trực tuyến',
                    ] as $key => $label)
                        <label class="is-wide">
                            {{ $label }}
                            <textarea name="{{ $key }}" maxlength="500" required>{{ old($key, $settings->get($key)) }}</textarea>
                        </label>
                    @endforeach
                    <div class="admin-chat-key-status" aria-label="Trạng thái Gemini">
                        @forelse ($geminiKeys as $key)
                            <span @class(['is-cooldown' => $key['status'] === 'cooldown'])>{{ $key['label'] }} · {{ $key['status'] === 'cooldown' ? 'Tạm nghỉ' : 'Sẵn sàng' }}</span>
                        @empty
                            <span class="is-cooldown">Chưa cấu hình Gemini key</span>
                        @endforelse
                    </div>
                    <button class="button button-primary" type="submit">Lưu cấu hình</button>
                </form>
            </details>
        </header>

        <div class="admin-chat-workspace">
            <aside class="admin-chat-list-pane" aria-label="Danh sách hội thoại">
                <div class="admin-chat-filters">
                    <input class="admin-chat-search" type="search" placeholder="Tìm khách hàng..." data-admin-chat-search>
                    <div class="admin-chat-filter-row" data-admin-chat-filters>
                        <button class="is-current" type="button" data-status="">Tất cả</button>
                        <button type="button" data-status="waiting_admin">Chờ nhân viên</button>
                        <button type="button" data-status="bot">Trợ lý</button>
                        <button type="button" data-status="admin">Đang hỗ trợ</button>
                        <button type="button" data-status="closed">Đã đóng</button>
                    </div>
                </div>
                <div class="admin-chat-list" data-admin-chat-list><p class="admin-chat-empty">Đang tải hội thoại...</p></div>
            </aside>

            <section class="admin-chat-detail" aria-live="polite">
                <header class="admin-chat-detail-head">
                    <div><h2 data-admin-chat-name>Chọn một hội thoại</h2><small data-admin-chat-status></small></div>
                    <div class="admin-chat-detail-actions" data-admin-chat-actions hidden>
                        <button class="is-primary" type="button" data-admin-chat-action="takeover">Nhận trò chuyện</button>
                        <button type="button" data-admin-chat-action="return-to-bot">Chuyển lại trợ lý</button>
                        <button type="button" data-admin-chat-action="close">Đóng</button>
                    </div>
                </header>
                <div class="admin-chat-transcript" data-admin-chat-transcript><p class="admin-chat-empty">Tin nhắn sẽ hiển thị tại đây.</p></div>
                <form class="admin-chat-reply" data-admin-chat-reply hidden>
                    <textarea name="message" maxlength="2000" placeholder="Nhập phản hồi..." required></textarea>
                    <button type="submit">Gửi</button>
                </form>
            </section>

            <aside class="admin-chat-context" data-admin-chat-context>
                <h3>Thông tin khách</h3>
                <p class="admin-chat-empty">Chọn hội thoại để xem thông tin cần thiết.</p>
            </aside>
        </div>
    </section>
@endsection
