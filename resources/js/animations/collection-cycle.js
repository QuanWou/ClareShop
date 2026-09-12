// One visible card at a time, in reading order. Never preload the entire image pool.
export function createCollectionCycle(root) {
    const states = [...root.querySelectorAll('[data-collection-card]')].map((card, index) => {
        let sources = [];
        try { sources = JSON.parse(card.dataset.collectionImages || '[]'); } catch { /* Keep cover. */ }
        const layers = [...card.querySelectorAll('[data-collection-layer]')];
        const mode = ['fade', 'slide', 'fade', 'lift', 'fade', 'slide'][index % 6];
        card.classList.add(`is-collection-transition-${mode}`);
        const active = Math.max(0, layers.findIndex((layer) => layer.classList.contains('is-active')));
        const sourceIndex = sources.indexOf(layers[active]?.src);
        return { card, sources, layers, index: Math.max(0, sourceIndex), active, visible: false, loading: false, mode };
    }).filter(({ sources, layers }) => sources.length > 1 && layers.length === 2);
    if (!states.length || !('IntersectionObserver' in window)) return () => {};

    let cursor = 0;
    let timer;
    let disposed = false;
    const pending = new Set();
    const timers = new Set();
    const cycle = () => {
        if (disposed || document.hidden) return;
        const visible = states.filter(({ card, visible, loading }) => visible && !loading && !card.matches(':hover, :focus-within'));
        if (visible.length) {
            const state = visible[cursor++ % visible.length];
            const nextIndex = (state.index + 1) % state.sources.length;
            const image = new Image();
            state.loading = true;
            pending.add(image);
            const release = () => { state.loading = false; pending.delete(image); image.onload = image.onerror = null; };
            image.onload = async () => {
                try { await image.decode(); } catch { /* Loaded image is still usable. */ }
                if (!disposed && state.visible && !document.hidden) {
                    const nextLayer = 1 - state.active;
                    state.layers[nextLayer].src = image.src;
                    state.layers[nextLayer].classList.add('is-active');
                    state.layers[state.active].classList.remove('is-active');
                    state.card.classList.add('is-collection-changing');
                    state.active = nextLayer;
                    const finish = setTimeout(() => {
                        state.card.classList.remove('is-collection-changing');
                        timers.delete(finish);
                    }, 1000);
                    timers.add(finish);
                }
                state.index = nextIndex;
                release();
            };
            image.onerror = () => { state.index = nextIndex; release(); };
            image.decoding = 'async';
            image.src = state.sources[nextIndex];
        }
        timer = setTimeout(cycle, 1600);
    };
    const sync = () => {
        clearTimeout(timer);
        if (!document.hidden && states.some((state) => state.visible)) timer = setTimeout(cycle, 1600);
    };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const state = states.find(({ card }) => card === entry.target);
            if (state) state.visible = entry.isIntersecting;
        });
        sync();
    }, { threshold: 0.15 });
    states.forEach(({ card }) => observer.observe(card));
    document.addEventListener('visibilitychange', sync);
    return () => {
        disposed = true;
        clearTimeout(timer);
        timers.forEach(clearTimeout);
        pending.forEach((image) => { image.onload = image.onerror = null; });
        pending.clear();
        observer.disconnect();
        document.removeEventListener('visibilitychange', sync);
        states.forEach(({ card, mode }) => card.classList.remove('is-collection-changing', `is-collection-transition-${mode}`));
    };
}
