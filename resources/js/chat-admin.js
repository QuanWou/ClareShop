const root = document.querySelector('[data-admin-chat]');

if (root) {
    const listEl = root.querySelector('[data-admin-chat-list]');
    const transcript = root.querySelector('[data-admin-chat-transcript]');
    const context = root.querySelector('[data-admin-chat-context]');
    const nameEl = root.querySelector('[data-admin-chat-name]');
    const statusEl = root.querySelector('[data-admin-chat-status]');
    const actions = root.querySelector('[data-admin-chat-actions]');
    const replyForm = root.querySelector('[data-admin-chat-reply]');
    const search = root.querySelector('[data-admin-chat-search]');
    const csrf = root.dataset.csrf;
    let selectedId = null;
    let status = '';
    let searchTimer;
    let loadingList = false;
    let loadingDetail = false;

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    };

    const endpoint = (id, action = '') => root.dataset.showUrl.replace('__ID__', id) + (action ? `/${action}` : '');
    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers ?? {}) },
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            throw new Error(payload.message ?? 'Không thể thực hiện thao tác.');
        }
        return response.json();
    };

    const loadList = async () => {
        if (loadingList) return;
        loadingList = true;
        try {
            const url = new URL(root.dataset.listUrl, window.location.origin);
            if (status) url.searchParams.set('status', status);
            if (search.value.trim()) url.searchParams.set('search', search.value.trim());
            const payload = await request(url);
            listEl.replaceChildren();
            if (!payload.conversations.length) listEl.append(el('p', 'admin-chat-empty', 'Không có hội thoại phù hợp.'));
            payload.conversations.forEach((conversation) => {
                const button = el('button', `admin-chat-list-item${selectedId === conversation.id ? ' is-current' : ''}`);
                button.type = 'button';
                const top = el('span', 'admin-chat-list-line');
                top.append(el('strong', '', conversation.customer), el('small', '', conversation.time));
                const bottom = el('span', 'admin-chat-list-line');
                bottom.append(el('small', '', conversation.status_label));
                if (conversation.unread) bottom.append(el('span', 'admin-chat-unread', conversation.unread));
                button.append(top, el('p', '', conversation.last_message || 'Chưa có tin nhắn'), bottom);
                button.addEventListener('click', () => { selectedId = conversation.id; loadList(); loadDetail(); });
                listEl.append(button);
            });
        } catch (error) {
            listEl.replaceChildren(el('p', 'admin-chat-empty', error.message));
        } finally { loadingList = false; }
    };

    const renderTranscript = (messages) => {
        transcript.replaceChildren();
        messages.forEach((message) => {
            if (message.type === 'system' || message.sender === 'system') {
                transcript.append(el('p', 'admin-chat-system', message.text));
                return;
            }
            const row = el('div', `admin-chat-message is-${message.sender}`);
            const bubble = el('div');
            bubble.append(el('p', '', message.text), el('small', '', `${message.sender_name ?? (message.sender === 'customer' ? 'Khách hàng' : 'Clare Assistant')} · ${message.time ?? ''}`));
            row.append(bubble);
            transcript.append(row);
        });
        transcript.scrollTop = transcript.scrollHeight;
    };

    const renderContext = (conversation) => {
        context.replaceChildren(el('h3', '', 'Thông tin khách'));
        const details = el('dl');
        [['Họ tên', conversation.customer.name], ['Email', conversation.customer.email], ['Điện thoại', conversation.customer.phone]].forEach(([label, value]) => {
            if (!value) return;
            const wrap = el('div');
            wrap.append(el('dt', '', label), el('dd', '', value));
            details.append(wrap);
        });
        context.append(details, el('h3', '', 'Đơn gần đây'));
        if (!conversation.orders.length) context.append(el('p', 'admin-chat-empty', 'Chưa có đơn hàng.'));
        conversation.orders.forEach((order) => {
            const item = el('div', 'admin-chat-order-context');
            item.append(el('strong', '', order.number), el('span', '', `${order.status} · ${order.payment}`), el('b', '', order.total));
            context.append(item);
        });
    };

    const syncActions = (conversation) => {
        actions.hidden = false;
        actions.querySelector('[data-admin-chat-action="takeover"]').hidden = conversation.status === 'admin';
        actions.querySelector('[data-admin-chat-action="return-to-bot"]').hidden = conversation.status !== 'admin';
        actions.querySelector('[data-admin-chat-action="close"]').hidden = conversation.status === 'closed';
        replyForm.hidden = conversation.status !== 'admin';
    };

    const loadDetail = async () => {
        if (!selectedId || loadingDetail) return;
        loadingDetail = true;
        try {
            const payload = await request(endpoint(selectedId));
            nameEl.textContent = payload.conversation.customer.name;
            statusEl.textContent = payload.conversation.status_label;
            syncActions(payload.conversation);
            renderTranscript(payload.messages);
            renderContext(payload.conversation);
        } catch (error) {
            transcript.replaceChildren(el('p', 'admin-chat-empty', error.message));
        } finally { loadingDetail = false; }
    };

    root.querySelectorAll('[data-status]').forEach((button) => button.addEventListener('click', () => {
        status = button.dataset.status;
        root.querySelectorAll('[data-status]').forEach((item) => item.classList.toggle('is-current', item === button));
        loadList();
    }));
    search.addEventListener('input', () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(loadList, 280); });
    actions.querySelectorAll('[data-admin-chat-action]').forEach((button) => button.addEventListener('click', async () => {
        if (!selectedId) return;
        button.disabled = true;
        try { await request(endpoint(selectedId, button.dataset.adminChatAction), { method: 'POST' }); await Promise.all([loadList(), loadDetail()]); }
        catch (error) { window.alert(error.message); }
        finally { button.disabled = false; }
    }));
    replyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const textarea = replyForm.querySelector('textarea');
        const message = textarea.value.trim();
        if (!message || !selectedId) return;
        const button = replyForm.querySelector('button');
        button.disabled = true;
        try {
            await request(endpoint(selectedId, 'reply'), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message }) });
            textarea.value = '';
            await Promise.all([loadList(), loadDetail()]);
        } catch (error) { window.alert(error.message); }
        finally { button.disabled = false; }
    });

    loadList();
    const timer = window.setInterval(() => { loadList(); if (selectedId) loadDetail(); }, 5000);
    window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
}
