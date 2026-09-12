import Lenis from 'lenis';

export function createSmoothScroll(gsap, ScrollTrigger) {
    const lenis = new Lenis({
        lerp: 0.13,
        smoothWheel: true,
        syncTouch: false,
        anchors: true,
        stopInertiaOnNavigate: true,
        prevent: (node) => node.matches?.('input, textarea, select, [role="dialog"], [data-lenis-prevent], .site-header'),
        virtualScroll: () => !document.querySelector('[role="dialog"][aria-modal="true"]:not([hidden])')
            && document.body.style.overflow !== 'hidden',
    });
    const tick = (time) => lenis.raf(time * 1000);
    const off = lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(tick);
    gsap.ticker.lagSmoothing(0);
    return () => {
        off();
        gsap.ticker.remove(tick);
        lenis.destroy();
        gsap.ticker.lagSmoothing(500, 33);
    };
}
