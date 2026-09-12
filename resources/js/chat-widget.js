const widget = document.querySelector('[data-chat-widget]');

if (widget) {
    const panel = widget.querySelector('[data-chat-panel]');
    const launcher = widget.querySelector('[data-chat-launcher]');
    const messagesEl = widget.querySelector('[data-chat-messages]');
    const quickActionsEl = widget.querySelector('[data-chat-quick-actions]');
    const statusEl = widget.querySelector('[data-chat-status]');
    const form = widget.querySelector('[data-chat-form]');
    const input = widget.querySelector('[data-chat-input]');
    const send = widget.querySelector('[data-chat-send]');
    const typing = widget.querySelector('[data-chat-typing]');
    const unread = widget.querySelector('[data-chat-unread]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const seen = new Set();
    let latestMessageId = 0;
    let isSending = false;
    let hasLoaded = false;
    let pollTimer;

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    };

    const safeLink = (url) => {
        try {
            const parsed = new URL(url, window.location.origin);
            return parsed.origin === window.location.origin ? parsed.href : null;
        } catch {
            return null;
        }
    };

    const appendAction = (parent, metadata) => {
        const url = safeLink(metadata?.action_url);
        if (!url || !metadata?.action_label) return;
        const link = el('a', 'clare-chat-inline-action', metadata.action_label);
        link.href = url;
        parent.append(link);
    };

    const productCards = (metadata) => {
        const list = el('div', 'clare-chat-product-list');
        (metadata?.products ?? []).slice(0, 4).forEach((product) => {
            const card = el('article', 'clare-chat-product');
            const analyzeProduct = () => {
                if (product.id) submitMessage(`Phân tích sản phẩm ${product.name}`, { productId: product.id });
            };
            if (product.id) {
                card.tabIndex = 0;
                card.setAttribute('role', 'button');
                card.setAttribute('aria-label', `Phân tích sản phẩm ${product.name ?? ''}`);
                card.addEventListener('click', (event) => {
                    if (!event.target.closest('a, button')) analyzeProduct();
                });
                card.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        analyzeProduct();
                    }
                });
            }
            const image = el('img');
            image.src = safeLink(product.image) ?? '/images/catalog/product-placeholder.svg';
            image.alt = product.name ?? '';
            image.loading = 'lazy';
            const copy = el('span', 'clare-chat-product-copy');
            copy.append(el('strong', '', product.name), el('b', '', product.price));
            if (product.compare_at_price) copy.append(el('del', '', product.compare_at_price));
            const meta = el('span', 'clare-chat-product-meta');
            meta.append(el('small', product.stock_label === 'Còn hàng' ? 'is-in-stock' : '', product.stock_label));
            const actions = el('span', 'clare-chat-product-actions');
            const productUrl = safeLink(product.url);
            if (productUrl) {
                const view = el('a', '', 'Xem chi tiết');
                view.href = productUrl;
                actions.append(view);
            }
            if (product.id) {
                const analyze = el('button', '', 'Phân tích');
                analyze.type = 'button';
                analyze.addEventListener('click', analyzeProduct);
                actions.append(analyze);
            }
            copy.append(meta, actions);
            card.append(image, copy);
            list.append(card);
        });
        const moreUrl = safeLink(metadata?.more_url);
        if (moreUrl) {
            const more = el('a', 'clare-chat-more', 'Xem thêm sản phẩm');
            more.href = moreUrl;
            list.append(more);
        }
        return list;
    };

    const orderCard = (order) => {
        const card = el('a', 'clare-chat-order');
        card.href = safeLink(order?.url) ?? '#';
        card.append(
            el('small', '', `Đơn ${order?.number ?? ''}`),
            el('strong', '', order?.status ?? ''),
            el('b', '', order?.total ?? ''),
            el('span', '', order?.payment ?? ''),
        );
        return card;
    };

    const voucherCards = (metadata) => {
        const list = el('div', 'clare-chat-vouchers');
        (metadata?.vouchers ?? []).forEach((voucher) => {
            const card = el('div', 'clare-chat-voucher');
            card.append(el('strong', '', voucher.code), el('span', '', voucher.offer), el('small', '', voucher.minimum));
            if (voucher.expires) card.append(el('small', '', `Hạn ${voucher.expires}`));
            list.append(card);
        });
        appendAction(list, metadata);
        return list;
    };

    const renderMessage = (message) => {
        if (seen.has(message.id)) return;
        seen.add(message.id);
        latestMessageId = Math.max(latestMessageId, Number(message.id) || 0);

        if (message.type === 'system' || message.sender === 'system') {
            messagesEl.append(el('p', 'clare-chat-system', message.text));
            return;
        }

        const row = el('div', `clare-chat-message is-${message.sender}`);
        const bubble = el('div', 'clare-chat-bubble');
        if (message.sender === 'admin' && message.sender_name) bubble.append(el('small', 'clare-chat-sender', message.sender_name));
        bubble.append(el('p', '', message.text));
        appendAction(bubble, message.metadata);
        row.append(bubble);
        messagesEl.append(row);

        if (message.type === 'product_card') messagesEl.append(productCards(message.metadata));
        if (message.type === 'order_card') messagesEl.append(orderCard(message.metadata?.order));
        if (message.type === 'voucher_card') messagesEl.append(voucherCards(message.metadata));
        if (message.metadata?.show_handoff) {
            const button = el('button', 'clare-chat-handoff', 'Gặp nhân viên');
            button.type = 'button';
            button.addEventListener('click', () => submitMessage('Tôi muốn gặp nhân viên hỗ trợ'));
            messagesEl.append(button);
        }
    };

    const applyPayload = (payload) => {
        statusEl.textContent = payload.conversation?.status_label ?? 'Trợ lý trực tuyến';
        (payload.messages ?? []).forEach(renderMessage);
        quickActionsEl.replaceChildren();
        (payload.quick_actions ?? []).forEach((action) => {
            const button = el('button', '', action.label);
            button.type = 'button';
            button.addEventListener('click', () => submitMessage(action.message));
            quickActionsEl.append(button);
        });
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const fetchMessages = async (after = 0) => {
        const url = new URL(widget.dataset.chatBootstrapUrl, window.location.origin);
        if (after) url.searchParams.set('after', String(after));
        const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) throw new Error('Chat unavailable');
        const payload = await response.json();
        applyPayload(payload);
        hasLoaded = true;
    };

    const submitMessage = async (value = input.value, context = {}) => {
        const message = value.trim();
        if (!message || isSending) return;
        isSending = true;
        send.disabled = true;
        input.disabled = true;
        typing.hidden = false;
        input.value = '';
        input.style.height = '';

        try {
            const response = await fetch(widget.dataset.chatMessageUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    message,
                    client_message_id: crypto.randomUUID(),
                    ...(context.productId ? { product_id: Number(context.productId) } : {}),
                }),
            });
            if (!response.ok) throw new Error('Message failed');
            applyPayload(await response.json());
        } catch {
            messagesEl.append(el('p', 'clare-chat-system is-error', 'Tin nhắn chưa gửi được. Bạn thử lại sau nhé.'));
        } finally {
            typing.hidden = true;
            isSending = false;
            send.disabled = false;
            input.disabled = false;
            input.focus({ preventScroll: true });
        }
    };

    const open = async () => {
        panel.hidden = false;
        launcher.setAttribute('aria-expanded', 'true');
        unread.hidden = true;
        if (!hasLoaded) {
            typing.hidden = false;
            try { await fetchMessages(); } catch { messagesEl.append(el('p', 'clare-chat-system is-error', 'Chat đang tạm gián đoạn. Bạn thử mở lại sau nhé.')); }
            typing.hidden = true;
        }
        input.focus({ preventScroll: true });
        window.clearInterval(pollTimer);
        pollTimer = window.setInterval(() => fetchMessages(latestMessageId).catch(() => {}), 5000);
    };

    const close = () => {
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        window.clearInterval(pollTimer);
        launcher.focus({ preventScroll: true });
    };

    launcher.addEventListener('click', () => panel.hidden ? open() : close());
    widget.querySelector('[data-chat-minimize]').addEventListener('click', close);
    widget.querySelector('[data-chat-close]').addEventListener('click', close);
    form.addEventListener('submit', (event) => { event.preventDefault(); submitMessage(); });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); submitMessage(); }
    });
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 90)}px`;
    });
    document.querySelectorAll('[data-chat-analyze-product]').forEach((button) => {
        button.addEventListener('click', async () => {
            const productId = Number(button.dataset.chatAnalyzeProduct);
            if (!productId) return;
            await open();
            await submitMessage(`Phân tích sản phẩm ${button.dataset.chatAnalyzeName ?? ''}`.trim(), { productId });
        });
    });
    window.addEventListener('pagehide', () => window.clearInterval(pollTimer), { once: true });

    const animationContainer = widget.querySelector('[data-chat-animation]');
    Promise.all([import('lottie-web'), import('./Chat.json')]).then(([lottieModule, animationModule]) => {
        const lottie = lottieModule.default ?? lottieModule;
        lottie.loadAnimation({
            container: animationContainer,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            animationData: animationModule.default ?? animationModule,
        });
        widget.classList.add('has-chat-animation');
    }).catch(() => {});
}
