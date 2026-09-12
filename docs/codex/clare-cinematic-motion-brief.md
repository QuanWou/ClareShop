# Clare Cinematic Motion Brief

## Goal
Add a premium cinematic motion system to the existing Clare Laravel storefront without rewriting the application architecture.

## Preserve the existing application
- Keep Laravel, Blade, routes, controllers, models, database schema, authentication, cart, checkout, orders, payments, vouchers, and admin logic intact unless a task explicitly asks to change them.
- Do not migrate Clare to React, Next.js, Vue, or another SPA framework solely for animation.
- Reuse existing Blade components and Vite pipeline where practical.
- Keep checkout, account, authentication, order management, and other transactional screens simple and usable.

## Motion direction
The reference style is cinematic 3D motion design where a flat composition gains depth, cards/images separate into layers, the camera rotates/zooms through the composition, and those layers settle into the actual website layout.

Desired language:
- premium, elegant, cinematic, smooth
- depth rather than gimmicky bounce
- deliberate camera-like movement
- continuous transitions between sections
- product imagery as the focal point

## Preferred stack
1. GSAP + ScrollTrigger for timelines, pinned sections, scrubbed animation, reveals, and transitions.
2. Lenis only where smooth scrolling materially improves the experience.
3. CSS 3D transforms/perspective for most layered-card and product-depth effects.
4. Three.js only for effects that genuinely need WebGL, such as a curved/wave field of repeated product cards, GLB models, or shader scenes.
5. Lazy-load heavy WebGL code and assets.

## Homepage motion concept
### Scene 1 — Intro
- Minimal Clare intro/logo.
- Avoid long forced loaders.

### Scene 2 — Hero depth composition
- Hero product starts as a designed composition.
- Supporting product details/cards/images separate in X/Y/Z space.
- Use perspective, rotateX/rotateY, translateZ, scale and blur sparingly.

### Scene 3 — Camera transition into real UI
- Use GSAP timeline driven by scroll.
- Camera-like composition rotates toward a frontal view.
- Floating elements settle into their actual DOM positions.
- Final frame must be a fully usable webpage section, not a video overlay.

### Scene 4 — Featured collection wave
- If used, create a ribbon/wave of repeated product cards or imagery.
- Prefer Three.js only for this scene if CSS cannot deliver adequate quality/performance.
- Transition the wave back into real product cards.

### Scene 5 — Product detail transition
- When feasible, visually carry the selected product image/card into the product-detail hero.
- Preserve navigation, accessibility, and fallback behavior.

## Performance rules
- Target smooth desktop animation and graceful mobile fallback.
- Respect `prefers-reduced-motion`.
- Simplify or disable expensive depth/WebGL effects on small screens or low-power devices.
- Avoid animating layout properties when transform/opacity can be used.
- Avoid large uncompressed video/image sequences unless specifically justified.
- Prevent horizontal overflow.
- Clean up GSAP ScrollTriggers/listeners when appropriate.

## Implementation approach
Before modifying code:
1. Inspect the existing Clare frontend structure and current dependencies.
2. Identify the exact Blade view/component for the target section.
3. Identify current CSS/JS that would conflict with the new motion.
4. Propose the smallest architectural change.
5. Implement one scene at a time and verify build/runtime before moving on.

Do not redesign unrelated business functionality while implementing motion.
