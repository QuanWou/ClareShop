import { addProductDepth } from './product-depth.js';

export function createHero(gsap, hero, desktop) {
    if (!hero) return;
    const composition = hero.querySelector('[data-depth-composition]');
    const copy = hero.querySelector('.cinematic-hero-copy');
    if (!composition || !copy) return;

    if (!desktop) {
        gsap.from(composition, {
            y: 22, duration: 0.8, ease: 'power2.out',
            scrollTrigger: { trigger: composition, start: 'top 95%', once: true },
        });
        return;
    }

    const timeline = gsap.timeline({
        scrollTrigger: {
            id: 'clare-hero', trigger: hero, start: 'top 90px',
            end: () => `+=${Math.min(900, window.innerHeight * 1.05)}`,
            pin: true, scrub: 0.85, anticipatePin: 1, invalidateOnRefresh: true,
        },
    });
    timeline.fromTo(composition, { rotationX: 8, rotationY: -13, rotation: -2, scale: 0.88 }, {
        rotationX: 5, rotationY: -6, rotation: 1, scale: 0.97, duration: 0.45, ease: 'power1.inOut',
    }, 0).to(composition, {
        rotationX: 0, rotationY: 0, rotation: 0, scale: 1, duration: 0.55, ease: 'power2.inOut',
    }, 0.45);
    timeline.fromTo(copy.querySelector('.cinematic-hero-intro'), { y: 12, opacity: 0.65 }, {
        y: 0, opacity: 1, duration: 0.35,
    }, 0.4);
    addProductDepth(timeline, hero);
}
