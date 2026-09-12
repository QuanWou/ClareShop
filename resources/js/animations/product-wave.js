export function createProductWave(gsap, root, desktop) {
    const section = root.querySelector('[data-product-wave]');
    if (!section) return;
    const tiles = [...section.querySelectorAll('[data-wave-tile]')];
    const cards = [...section.querySelectorAll('[data-wave-grid] > .product-card')];

    if (desktop && tiles.length) {
        const wave = gsap.timeline({
            scrollTrigger: {
                id: 'clare-wave', trigger: section, start: 'top 95%', end: 'top 5%',
                scrub: 0.7, invalidateOnRefresh: true,
            },
        });
        tiles.forEach((tile, index) => {
            const phase = index * 0.55;
            wave.fromTo(tile, {
                y: Math.sin(phase) * 65, z: Math.cos(phase) * 80,
                rotationY: -48, rotationX: Math.sin(phase) * 16, rotation: Math.cos(phase) * 6,
            }, {
                y: Math.sin(phase + 1.8) * 42, z: Math.cos(phase + 1.8) * 45,
                rotationY: 18, rotationX: 0, rotation: 0, duration: 0.6, ease: 'none',
            }, 0).to(tile, {
                y: 0, z: 0, rotationY: 0, rotationX: 0, rotation: 0, duration: 0.4, ease: 'power1.inOut',
            }, 0.6);
        });
    }
    cards.forEach((card, index) => {
        gsap.from(card, {
            y: desktop ? 58 : 18, rotationX: desktop ? 7 : 0, opacity: 0.55,
            duration: 0.85, delay: (index % 3) * 0.08, ease: 'power2.out',
            scrollTrigger: { trigger: card, start: 'top 94%', once: true },
        });
    });
}
