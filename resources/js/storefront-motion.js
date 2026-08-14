const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const revealElements = [...document.querySelectorAll('[data-reveal], [data-reveal-item]')];

document.querySelectorAll('[data-reveal-group]').forEach((group) => {
    group.querySelectorAll(':scope > [data-reveal-item]').forEach((item, index) => {
        item.style.setProperty('--reveal-delay', `${Math.min(index, 5) * 85}ms`);
    });
});

if (revealElements.length > 0) {
    document.documentElement.classList.add('js-motion');

    if (reducedMotion || !('IntersectionObserver' in window)) {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    } else {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            },
            {
                rootMargin: '0px 0px -9% 0px',
                threshold: 0.12,
            },
        );

        revealElements.forEach((element) => {
            if (element.hasAttribute('data-reveal-immediate')) {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => element.classList.add('is-visible'));
                });

                return;
            }

            revealObserver.observe(element);
        });
    }
}

const storefrontHeader = document.querySelector(
    '.catalog-home .site-header, .catalog-products-page .site-header, .catalog-collection-page .site-header, .catalog-search-page .site-header, .catalog-product-detail-page .site-header',
);

if (storefrontHeader) {
    const syncHeader = () => storefrontHeader.classList.toggle('is-scrolled', window.scrollY > 24);

    syncHeader();
    window.addEventListener('scroll', syncHeader, { passive: true });
}

const parallaxElements = reducedMotion ? [] : [...document.querySelectorAll('[data-parallax]')];

if (parallaxElements.length > 0) {
    let parallaxFrame;

    const updateParallax = () => {
        const viewportHeight = window.innerHeight;
        const viewportCenter = viewportHeight / 2;

        parallaxElements.forEach((element) => {
            const rect = element.getBoundingClientRect();

            if (rect.bottom < -120 || rect.top > viewportHeight + 120) {
                return;
            }

            const elementCenter = rect.top + rect.height / 2;
            const travel = Number(element.dataset.parallax ?? 0);
            const progress = Math.max(-1, Math.min(1, (viewportCenter - elementCenter) / (viewportCenter + rect.height / 2)));

            element.style.setProperty('--parallax-y', `${(progress * travel).toFixed(2)}px`);
        });

        parallaxFrame = undefined;
    };

    const requestParallaxUpdate = () => {
        if (parallaxFrame) {
            return;
        }

        parallaxFrame = window.requestAnimationFrame(updateParallax);
    };

    updateParallax();
    window.addEventListener('scroll', requestParallaxUpdate, { passive: true });
    window.addEventListener('resize', requestParallaxUpdate);
}

if (!reducedMotion && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.querySelectorAll('[data-ambient]').forEach((surface) => {
        surface.addEventListener('pointermove', (event) => {
            const rect = surface.getBoundingClientRect();

            surface.style.setProperty('--pointer-x', `${((event.clientX - rect.left) / rect.width) * 100}%`);
            surface.style.setProperty('--pointer-y', `${((event.clientY - rect.top) / rect.height) * 100}%`);
        });
    });
}
