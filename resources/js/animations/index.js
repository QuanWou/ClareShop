import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { createCleanup } from './cleanup.js';
import { createSmoothScroll } from './smooth-scroll.js';
import { createHero } from './hero-cinematic.js';
import { createProductWave } from './product-wave.js';
import { createScrollReveals } from './scroll-reveal.js';
import { createCollectionCycle } from './collection-cycle.js';

let unmount;

export function mountCinematicHome() {
    if (unmount) return unmount;
    const root = document.querySelector('.clare-cinematic main');
    const hero = root?.querySelector('[data-cinematic-home]');
    if (!root || !hero) return () => {};
    gsap.registerPlugin(ScrollTrigger);
    const cleanup = createCleanup();
    let media;
    const body = root.closest('body');
    const toggle = hero.querySelector('[data-motion-toggle]');
    let paused = body.classList.contains('cinematic-motion-paused');
    cleanup.add(() => media?.revert());

    const setup = () => {
        media?.revert();
        media = gsap.matchMedia();
        media.add({
            desktop: '(min-width: 1100px) and (min-height: 700px) and (hover: hover) and (pointer: fine)',
            reduce: '(prefers-reduced-motion: reduce)',
            all: '(min-width: 0px)',
        }, (context) => {
            if (toggle) {
                toggle.disabled = context.conditions.reduce;
                toggle.setAttribute('aria-pressed', String(paused || context.conditions.reduce));
                toggle.textContent = context.conditions.reduce ? 'Đã giảm chuyển động' : paused ? 'Bật chuyển động' : 'Giảm chuyển động';
            }
            if (context.conditions.reduce || paused) return;
            const desktop = context.conditions.desktop;
            const stopSmooth = desktop ? createSmoothScroll(gsap, ScrollTrigger) : () => {};
            const stopCollections = createCollectionCycle(root);
            try {
                createHero(gsap, hero, desktop);
                createProductWave(gsap, root, desktop);
                createScrollReveals(gsap, root, desktop);
            } catch (error) {
                stopSmooth();
                stopCollections();
                throw error;
            }
            return () => { stopCollections(); stopSmooth(); };
        });
    };
    try {
        setup();
    } catch (error) {
        cleanup.run();
        throw error;
    }
    if (toggle) {
        toggle.hidden = false;
        const onToggle = () => {
            paused = !paused;
            body.classList.toggle('cinematic-motion-paused', paused);
            setup();
            toggle.scrollIntoView({ block: 'nearest', behavior: 'instant' });
        };
        toggle.addEventListener('click', onToggle);
        cleanup.add(() => { toggle.removeEventListener('click', onToggle); toggle.hidden = true; });
    }

    let refreshFrame;
    const refresh = () => {
        cancelAnimationFrame(refreshFrame);
        refreshFrame = requestAnimationFrame(() => ScrollTrigger.refresh());
    };
    // Dimensions are reserved in CSS; one refresh after fonts/load handles late font metrics.
    document.fonts?.ready.then(() => { if (unmount) refresh(); });
    window.addEventListener('load', refresh, { once: true });
    cleanup.add(() => { window.removeEventListener('load', refresh); cancelAnimationFrame(refreshFrame); });
    const onPageHide = () => unmount?.();
    const onPageShow = (event) => {
        if (event.persisted) {
            window.removeEventListener('pageshow', onPageShow);
            mountCinematicHome();
        }
    };
    window.addEventListener('pagehide', onPageHide, { once: true });
    window.addEventListener('pageshow', onPageShow);
    cleanup.add(() => window.removeEventListener('pagehide', onPageHide));
    unmount = () => { cleanup.run(); unmount = undefined; };
    return unmount;
}
