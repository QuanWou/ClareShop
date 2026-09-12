// Local layer offsets; zero is always the real, accessible Blade layout.
export function addProductDepth(timeline, hero) {
    const layers = [...hero.querySelectorAll('[data-depth-layer]')];
    const positions = {
        detail: { x: -30, y: 20, z: 80, rotationY: 12, rotation: -5 },
        product: { x: 20, y: -10, z: 60, rotationY: -8, rotation: 4 },
        note: { x: -10, y: -25, z: 40, rotation: 8 },
    };
    layers.forEach((layer) => {
        timeline.fromTo(layer, { x: 0, y: 0, z: 0, rotationY: 0, rotation: 0 }, {
            ...positions[layer.dataset.depthLayer], duration: 0.3, ease: 'power1.inOut',
        }, 0.04).to(layer, {
            x: 0, y: 0, z: 0, rotationY: 0, rotation: 0, duration: 0.44, ease: 'power2.inOut',
        }, 0.56);
    });
}
