# Clare Home cinematic implementation

## Reference and plan — 2026-09-08

Reference inspected: `docs/references/cinematic-reference.mp4` (7.94 seconds, 436 × 514), sampled every 0.5 seconds. The opening rotates an oblique board toward the viewer while smaller cards rise off its surface; around 3–5 seconds the composition becomes a readable two-column interface. The ending is a repeated-card ribbon with an undulating profile. Adapt this spatial language, not its branding, colors or UI.

1. Keep the real Home Blade, CMS copy, categories, featured products and existing product-card component. Replace the Home hero composition only; preserve lamp interaction.
2. GSAP + ScrollTrigger own the camera, layer offsets, reveals and ribbon. Lenis is limited to fine-pointer desktop Home; touch, reduced motion and unrelated pages use native scrolling. No WebGL required.
3. Hero: perspective 1400px; camera starts rotated X 8°, Y −13°, Z −2°, scale .88. At 20% supporting layers separate up to 70px XY / 110px Z. At 40% camera approaches. By 100% transforms are zero, the same DOM is a usable editorial composition. Desktop pin distance is capped at 900px; tablets/mobile are unpinned.
4. Repeated decorative product images form a 3D ribbon; the actual linked product cards beneath it settle from shallow depth into their normal grid. Decorative duplicates never enter the tab order.
5. Cream #f7f3eb, paper #fffdf8, wine #692e35, ink #2d2722, muted #766b60, olive #69705a. Existing Be Vietnam Pro / Noto Serif; no split graphemes or negative Vietnamese tracking.
6. Progressive enhancement: all content is readable in default HTML/CSS. MatchMedia contexts revert animations on viewport/preference changes. Page lifecycle destroys ticker/listeners/observers and reinstalls on bfcache return. Below-fold images lazy-load; no video shipped to clients.

## Future card → detail transition

Use a stable `data-motion-product` identity and the exported product image descriptor, but do not intercept navigation. A later phase can opt Home/Collection/Product Detail into native cross-document View Transitions after confirming browser support and matching the selected variant's image on both pages. Normal anchors, modified clicks, back/forward, server validation and reduced-motion navigation must remain native fallbacks.

## Verification

Record build, targeted tests and browser checks here after implementation. Existing transactional edits in the working tree predate this task and are deliberately preserved.
