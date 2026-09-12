// A stable source contract for a later native cross-document View Transition.
// No click interception, session storage, cloned overlays or navigation delays.
export function describeProductImage(source) {
    const card = source?.closest('[data-motion-product]');
    const image = card?.querySelector('[data-motion-image]');
    const link = card?.matches('a[href]') ? card : card?.querySelector('a[href]');
    if (!card || !image || !link) return null;
    return { key: card.dataset.motionProduct, src: image.currentSrc || image.src, alt: image.alt, href: link.href };
}
