export function createScrollReveals(gsap, root, desktop) {
    root.querySelectorAll('[data-reveal], [data-reveal-item]').forEach((element) => {
        // The product grid has its own transition. Never split Vietnamese text into letters.
        if (element.closest('[data-wave-grid]')) return;
        gsap.from(element, {
            y: desktop ? 26 : 14, opacity: 0.55, duration: 0.8, ease: 'power2.out',
            scrollTrigger: { trigger: element, start: 'top 96%', once: true },
        });
    });
    if (desktop) {
        root.querySelectorAll('.brand-banner-media img, .story-visual-media img').forEach((image) => {
            gsap.fromTo(image, { yPercent: -3, scale: 1.08 }, {
                yPercent: 3, scale: 1.08, ease: 'none',
                scrollTrigger: { trigger: image.parentElement, start: 'top bottom', end: 'bottom top', scrub: 0.8 },
            });
        });
    }
}
