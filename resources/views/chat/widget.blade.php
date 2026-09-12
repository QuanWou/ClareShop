<section
    class="clare-chat"
    data-chat-widget
    data-chat-bootstrap-url="{{ route('chat.bootstrap') }}"
    data-chat-message-url="{{ route('chat.messages.store') }}"
    aria-label="Hỗ trợ trực tuyến Clare"
>
    <div class="clare-chat-panel" role="dialog" aria-modal="false" aria-labelledby="clare-chat-title" hidden data-chat-panel>
        <header class="clare-chat-header">
            <div>
                <span class="clare-chat-presence" aria-hidden="true"></span>
                <p id="clare-chat-title">{{ $chatSettings->get('assistant_name') }}</p>
                <small data-chat-status>Trợ lý trực tuyến</small>
            </div>
            <div class="clare-chat-header-actions">
                <button type="button" aria-label="Thu nhỏ cửa sổ chat" data-chat-minimize>−</button>
                <button type="button" aria-label="Đóng cửa sổ chat" data-chat-close>×</button>
            </div>
        </header>

        <div class="clare-chat-messages" aria-live="polite" aria-relevant="additions" data-chat-messages></div>
        <div class="clare-chat-typing" aria-label="Đang trả lời" hidden data-chat-typing><i></i><i></i><i></i></div>
        <div class="clare-chat-quick-actions" data-chat-quick-actions></div>

        <form class="clare-chat-composer" data-chat-form>
            <label class="sr-only" for="clare-chat-message">Nhập tin nhắn</label>
            <textarea id="clare-chat-message" name="message" rows="1" maxlength="1200" placeholder="Nhập tin nhắn..." required data-chat-input></textarea>
            <button type="submit" aria-label="Gửi tin nhắn" data-chat-send>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m4 12 16-8-5 16-3.2-6.1L4 12Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m11.8 13.9 3-3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            </button>
        </form>
    </div>

    <button class="clare-chat-launcher" type="button" aria-label="Mở Clare Assistant" aria-expanded="false" data-chat-launcher>
        <span class="clare-chat-animation" data-chat-animation aria-hidden="true"></span>
        <span class="clare-chat-launcher-fallback" aria-hidden="true">Chat</span>
        <span class="clare-chat-unread" data-chat-unread hidden>1</span>
    </button>
</section>
