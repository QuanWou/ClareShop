# Cinematic Web Experiences Guide

Use this guide to plan or generate premium scroll-driven websites with GSAP motion, Three.js scenes, custom shaders, and cinematic loading screens inside prompt-based site builders without relying on an IDE-driven workflow.

## Scope

This workflow is derived from multiple real-world cinematic website builds.

Treat the process as a repeatable system:

1. Lock visual identity.
2. Choose the animation stack.
3. Architect sections before motion.
4. Write exact animation specs.
5. Declare files and assets explicitly.
6. Lock responsive behavior.

## Phase 1: Define Visual Identity First

Do not start with animations. First lock the brand tokens that the builder should turn into CSS variables and typography.

Specify exact values for:

- background color
- card or surface color
- primary brand color
- accent color
- foreground color
- muted foreground color
- one display font and one body font from Google Fonts
- only the font weights you actually need

Use exact `hsl(...)` or hex values. Avoid vague labels like "dark blue" or "light gray."

Example token set:

- Background: `#000`
- Card: `#252525`
- Primary: `hsl(245, 100%, 64%)`
- Accent: `hsl(75, 100%, 50%)`
- Foreground: `white`
- Muted: about `63%` gray
- Fonts: `Inter` weights `300 400 500`

## Phase 2: Choose the Animation Stack

Choose the stack before prompt writing. It controls prompt complexity, dependencies, asset requirements, and performance constraints.

### Stack options

`GSAP only`

- Use for scroll reveals, text choreography, hover motion, parallax, pinning, and cursor tracking.
- Best fit when the experience is mostly typographic or 2D.
- This is the 2D scroll-storytelling pattern.

`GSAP + Three.js with GLB asset`

- Use when a real 3D object should float, rotate, and react to scroll.
- Explicitly define asset paths and loader usage.
- This is the GLB-based 3D pattern.

`GSAP + Three.js with procedural shader scene`

- Use when the scene should be fully code-generated with no model files.
- This is the highest-impact option and the hardest to prompt well.
- This is the procedural shader pattern.

### Dependency rule

Always pin the Three.js version in the prompt:

```text
Install three@0.136.0, @types/three@0.136.0
```

Version drift causes silent breakage in loaders, typings, and examples.

### Smooth scroll rule

If the site uses Three.js, prefer adding Lenis so the 3D loop and scroll-driven motion feel smoother and more premium.

## Phase 3: Architect Sections Before Writing Animations

Prompt-based site builders usually generate the page top to bottom. Your prompt sections often become the actual DOM structure, so define the information architecture first.

Recommended section map:

- `Loading screen` (optional): only for ultra-premium experiences. Define colors, elements, progress logic, and dismiss condition.
- `Navbar` (fixed): define explicit z-index, links, logo, and mobile behavior.
- `Hero` (`100vh`): define the visual centerpiece, headline, pinning, and how it exits on scroll.
- `Content sections` (`100vh` to `200vh` each): define min-height, content type, and the trigger each section owns.
- `Footer` or `Outro`: define CTA, contact block, social links, and text reveal behavior.

Treat sections as animation stages, not just layout boxes.

## Phase 4: Write Precise Animation Specs

Do not ask for generic motion. Describe exact transforms, timing, trigger points, and ownership.

Bad:

```text
the hero slides in
```

Good:

```text
animate from x:-100, scale:0.3, rotation:15 to normal as it enters viewport
```

### Four motion categories to specify

`Scroll-driven GSAP ScrollTrigger`

- Define `trigger`, `start`, `end`, `scrub`, and exact from/to transforms.
- Example: `scale: 0.75`, `rotation: -15`, `y: 250` during hero pinning.

`Viewport entry animations`

- Define initial state, final state, trigger point, and stagger.
- Example: section enters from `x:-100, scale:0.3, rotation:15, opacity:0`.

`CSS keyframes`

- Define animated property, keyframe stops, duration, easing, and repeat behavior.
- Example: infinite eye rotation or pulse height.

`Mouse tracking via GSAP`

- Define the target, cursor delta multiplier, easing, and smoothing.
- Example: sphere and face follow cursor with `power2.out`.

`3D scroll rotation`

- Define the axis, total rotation range, and how scroll progress maps to it.
- Example: `model.rotation.x = scrollProgress * Math.PI * 4 + 0.5`

### Preferred wording pattern

Use animation lines in this format:

```text
[element]: [library] [properties] from [start] to [end]
trigger: [start] / [end] / scrub: [value]
```

## Phase 5: Specify Files and External Assets

The builder will not invent assets for you. Declare every file, path, and loading expectation.

Pattern examples:

`2D image-led composition`

- Upload `sphere.png` and `eye.png`
- Reference them as `/sphere.png` and `/eye.png`

`GLB-driven product scene`

- Upload `chair.glb`
- Reference it as `/assets/chair.glb`
- Explicitly call for `GLTFLoader` from `three/examples/jsm/loaders/GLTFLoader.js`

`Fully procedural scene`

- Use no external assets
- Build the entire scene with `BoxGeometry`, `ShaderMaterial`, and code-generated textures
- Draw small texture assets, such as a star texture, on a `64x64` canvas in code

### Asset strategy

If the project can be fully procedural, it becomes easier to ship and harder to break because there are no external file dependencies. Use this only when you can clearly describe the geometry, material, and motion system.

### Optional companion dependency for asset generation

If the project needs source images, textures, video backgrounds, or other media assets that do not exist yet, pair this workflow with the `fal-ai-community/skills` repo:

`https://github.com/fal-ai-community/skills`

Use that companion skill first to generate or edit the required assets, then bring the final files back into this workflow with explicit paths and usage instructions.

### Optional companion dependency for text layout

If the project is typography-heavy or depends on very controlled multiline text lockups, pair this workflow with `pretext`:

`https://github.com/chenglou/pretext`

Use it when the frontend needs accurate multiline text measurement and layout without repeated DOM measurement/reflow. This is especially helpful for editorial headlines, balanced callouts, canvas/SVG text rendering, and dynamic text-heavy interfaces.

## Phase 6: Lock Responsive Behavior

Do not let mobile behavior emerge by accident. Add a dedicated responsive block at the end of the prompt.

Always specify:

- `clamp()` typography with min, preferred viewport width, and max
- hamburger behavior below `768px`
- grid-to-stack or flex-to-column collapse rules
- mobile 3D camera distance adjustments
- `overflow-x: hidden` on `html`, `body`, and `#root`

### Responsive defaults

- Headline sizing example: `clamp(36px, 12vw, 225px)`
- Mobile menu: choose full-screen overlay or slide-in panel
- Camera rule: `camera.position.z = maxDim * 3` on mobile versus `maxDim * 1.75` on desktop

## Universal Prompt Template

Use this as the base prompt shape when asking the builder to generate the site:

```text
Build a [single-page / multi-section] [type] website called [NAME].
[aesthetic description]: [dark/light] theme, [adjective] aesthetic.

Tech & Libraries
- [Three.js if 3D: Install three@0.136.0, @types/three@0.136.0]
- GSAP with ScrollTrigger for scroll-based animations
- [Lenis for smooth scrolling — add if using Three.js]
- Google Fonts: [Font 1] ([weights]) + [Font 2] ([weights])
- Tailwind CSS + shadcn/ui components + lucide-react for icons

Color Palette
- Background: [exact hex or hsl]
- Card: [exact hex]
- Primary: [exact hex or hsl]
- Accent: [exact hex or hsl]
- Foreground: white / [exact]
- Muted foreground: [exact]

[3D Scene — if applicable]
- [Canvas z-index, pointer-events, background]
- [Asset loading: path + loader]
- [Lighting setup: ambient / directional / point values]
- [On load animation]
- [Scroll-driven behavior: exact formula]

Sections

1. [SECTION NAME] ([height, behavior]):
   - [Element 1]: [description]
   - [Element 2]: [description]

Animations
- [Section/element]: [library] [property] from [start] to [end]
  trigger: [start] / [end] / scrub: [value]
- CSS keyframes: [name] ([property] [values], [duration] [easing] infinite)
- Mouse tracking: [elements] follow cursor with gsap.to [ease]

Responsive
- Font sizes: clamp() throughout
- Mobile hamburger (< 768px): [behavior]
- Layout: [desktop] to [mobile]
- overflow-x: hidden on html, body, #root
```

## Cross-Project Patterns

### Fixed fullscreen canvas for 3D

For Three.js builds, place the canvas in a fixed fullscreen layer below the scrollable HTML:

- `position: fixed`
- `inset: 0`
- `pointer-events: none`
- `z-index: 10`

Let the HTML scroll normally. The 3D scene reacts by reading a shared scroll progress variable.

### Scroll progress variable

Use one normalized `scrollProgress` value from `0` to `1` as the bridge between `ScrollTrigger` and the Three.js render loop.

```javascript
ScrollTrigger.create({
  trigger: ".content-container",
  start: "top top",
  end: "bottom bottom",
  scrub: 1.5,
  onUpdate: (self) => {
    scrollProgress = self.progress;
  }
});
```

Route camera shifts, model rotation, shader uniforms, or particle motion through that single variable.

### Accent-color discipline

Across all three builds, the visual system stays restrained:

- near-black background
- one high-contrast accent
- accent applied to CTA, highlights, and active states

Examples:

- lime accent
- warm beige accent
- blue `#4488FF`

### Section-by-section ownership

Give each major section its own trigger and purpose:

- hero pinning owns the hero transformation
- content entry owns the section reveal
- outro owns final text or CTA reveal

This reduces trigger conflicts and makes prompt-based site builds easier to debug.

## GSAP Cheat Sheet

```javascript
// Hero pinning
gsap.to(heroSection, {
  scale: 0.75,
  rotation: -15,
  y: 250,
  scrollTrigger: {
    trigger: heroSection,
    start: "top top",
    end: "bottom top",
    scrub: 1,
    pin: true
  }
});

// Content entry
gsap.from(contentSection, {
  x: -100,
  scale: 0.3,
  rotation: 15,
  opacity: 0,
  scrollTrigger: {
    trigger: contentSection,
    start: "top 80%",
    end: "top 20%",
    scrub: 1
  }
});

// Text line reveal
gsap.from(lines, {
  y: 70,
  opacity: 0,
  stagger: 0.1,
  scrollTrigger: {
    trigger: section,
    start: "top 75%"
  }
});

// Mouse tracking
document.addEventListener("mousemove", (e) => {
  const x = (e.clientX / window.innerWidth - 0.5) * 30;
  const y = (e.clientY / window.innerHeight - 0.5) * 30;
  gsap.to(sphere, {
    x,
    y,
    duration: 0.8,
    ease: "power2.out"
  });
});
```

```css
@keyframes rotateEyes {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { height: 50px; }
  50% { height: 80px; }
}
```

## Prompting Checklist

Before finalizing a builder prompt, verify:

- visual tokens are exact
- stack choice is explicit
- `three@0.136.0` is pinned if Three.js is involved
- each section has a role and rough height
- each animation has explicit transforms and trigger settings
- every asset path is declared
- responsive behavior is specified in a dedicated block
- `overflow-x: hidden` is applied to `html`, `body`, and `#root`

## Output Style

When using this guide to answer a user request:

- keep recommendations cinematic and specific
- prefer exact values over adjectives
- default to one punchy accent color
- explain tradeoffs when choosing between GSAP-only, GLB-based 3D, and procedural shader scenes
- if the user has not chosen a stack yet, decide one based on complexity, asset availability, and desired visual impact
