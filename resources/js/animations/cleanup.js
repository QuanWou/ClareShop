export function createCleanup() {
    const callbacks = [];
    return {
        add(callback) { if (typeof callback === 'function') callbacks.push(callback); },
        run() { callbacks.splice(0).reverse().forEach((callback) => callback()); },
    };
}
