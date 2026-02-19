# @frontend-perf — Frontend Performance Optimization

**Role**: Optimize client-side performance for fast, responsive user experiences

**When to use**: When optimizing page load times, Core Web Vitals, bundle sizes, or client-side rendering performance

**Secondary skills**: `@performance` (backend performance), `@dev` (implementation), `@qa` (performance testing)

---

## Core Principles

1. **Measure first, optimize second** — Use real metrics, not assumptions
2. **Optimize for perceived performance** — What users feel matters more than raw numbers
3. **Progressive enhancement** — Core content works without JavaScript
4. **Budget-driven development** — Set performance budgets and enforce them
5. **User-centric metrics** — Focus on Core Web Vitals (LCP, FID/INP, CLS)

## Current Project Profile (Moodle frontend)

In this repository, frontend performance work is mainly:
- Moodle block/local plugin render output (PHP + Mustache)
- plugin CSS (`styles.css`)
- AMD modules under `amd/src/` (RequireJS)

Priorities for this stack:
- Keep dashboard blocks lightweight: avoid heavy client-side rendering for data that can be rendered server-side.
- Minimize duplicated queries triggered by frontend cards/widgets by aligning with backend capability checks and cached data sources.
- Keep AMD payloads focused; do not introduce framework-scale JS for simple interactions.
- Preserve accessibility and readability while optimizing (no performance change that regresses contrast/usable interaction states).

---

## Core Web Vitals

### Largest Contentful Paint (LCP) — Target: <2.5s

**What it measures**: Time until the largest content element is visible

**Common causes of poor LCP**:
- Slow server response times
- Render-blocking JavaScript/CSS
- Slow resource load times (images, fonts)
- Client-side rendering delays

**Optimization strategies**:
- Preload critical resources (hero images, above-fold fonts)
- Use optimized image formats (AVIF/WebP with fallbacks)
- Inline critical CSS; defer non-critical CSS
- Serve images at the correct size for the viewport
- Use `priority` / `fetchpriority="high"` on the LCP image

### First Input Delay (FID) / Interaction to Next Paint (INP) — Target: <100ms

**What it measures**: Time from user interaction to browser response

**Common causes of poor FID/INP**:
- Heavy JavaScript execution blocking the main thread
- Large bundles parsing/compiling at load
- Long-running event handlers

**Optimization strategies**:
- Code-split: load only what the current view needs
- Defer non-critical scripts (analytics, ads)
- Break up long-running tasks; yield to the browser between chunks
- Move heavy computation to Web Workers

### Cumulative Layout Shift (CLS) — Target: <0.1

**What it measures**: Visual stability — how much content shifts unexpectedly

**Common causes of poor CLS**:
- Images without explicit width/height
- Ads or embeds without reserved space
- Web fonts causing FOIT/FOUT layout shifts
- Dynamic content injected above existing content

**Optimization strategies**:
- Always set explicit width and height on images
- Reserve space for ads/embeds with min-height
- Use `font-display: swap` and preload critical fonts
- Use CSS `contain: layout` for dynamic sections

---

## Bundle Size Optimization

### Analyze before optimizing

Run a bundle analyzer to see what's actually large. For webpack-based projects: `@next/bundle-analyzer`, `webpack-bundle-analyzer`. For other bundlers: Rollup Visualizer, Vite's `rollup-plugin-visualizer`. Check package size before adding a dependency — tools like `bundlephobia` give size + tree-shakability at a glance.

### Core strategies

**Tree shaking — eliminate dead code**

Import only what you use. Prefer named imports from ES modules over default imports of entire libraries. When a utility can be written in a few lines (debounce, clamp, format), prefer that over importing a large library for one function.

**Code splitting by route**

Each route/page should only load the JavaScript it needs. Load heavy components dynamically so they don't block initial render.

**Lazy load below-the-fold content**

Components that aren't visible on initial load don't need to be in the critical bundle. Wrap them in lazy/dynamic imports with a loading fallback.

**Choose lighter alternatives**

Before installing a dependency, check its size and whether a native API covers the use case. Common swaps: `date-fns` or `dayjs` over `moment`, native `fetch` over `axios`, native array methods over utility libraries.

---

## Image Optimization

### Principles

- Serve images in modern formats (AVIF → WebP → JPEG/PNG fallback)
- Always specify width and height to prevent layout shift
- Use responsive images (`srcset` + `sizes`) so mobile devices don't download desktop-sized images
- Lazy load images below the fold; eager-load the LCP image with high priority
- Compress images at the right quality level — 80–85% is usually indistinguishable from lossless

### Lazy loading

Use the native `loading="lazy"` attribute on `<img>` for below-fold images. Use `loading="eager"` (or omit the attribute) on the LCP image. For more control, IntersectionObserver lets you trigger loading when an element enters the viewport.

---

## JavaScript Performance

### Minimize main thread work

The main thread handles user input, rendering, and JavaScript execution. Long tasks (>50ms) block interactivity. Strategies:
- Move heavy computation to Web Workers
- Break large loops into chunks and yield between them (`setTimeout(0)` or `scheduler.yield()`)
- Debounce expensive event handlers (resize, scroll, input)
- Throttle operations that fire continuously (scroll position tracking)

### Optimize re-renders (component frameworks)

Unnecessary re-renders waste main thread time. General principles:
- Memoize expensive derived values so they only recalculate when inputs change
- Stabilize function references passed as props so child components don't re-render when the parent does
- Use shallow equality for re-render comparison where possible
- Virtualize long lists — render only the items visible in the viewport

---

## CSS Performance

### Critical CSS

Inline the CSS needed to render above-the-fold content in the `<head>`; load the rest asynchronously. This prevents a render-blocking round trip for the stylesheet.

### Remove unused CSS

Use a tool that analyzes your templates and strips CSS classes that are never referenced. Most CSS frameworks with JIT compilation (Tailwind, etc.) do this automatically when configured with the right content paths.

### Avoid runtime CSS-in-JS on critical paths

Libraries that generate styles at runtime add JavaScript execution cost on every render. Prefer zero-runtime alternatives (vanilla-extract, linaria) or CSS Modules for performance-critical components.

---

## Caching Strategies

### HTTP caching

Static assets (JS bundles, images, fonts) with content-hashed filenames can be cached indefinitely (`Cache-Control: public, max-age=31536000, immutable`). HTML and API responses need shorter TTLs or `no-cache` with revalidation.

### Service worker caching

A service worker can implement cache-first for static assets (serve from cache, update in background) and network-first for API data (try network, fall back to cache). Use only when you have a clear offline or performance requirement — service workers add complexity.

### Client-side data caching

Use a data-fetching library (SWR, React Query, Apollo) that handles deduplication, background revalidation, and stale-while-revalidate semantics. Don't reinvent this with raw `useEffect` + `useState`.

---

## Third-Party Scripts

Third-party scripts (analytics, ads, chat widgets, A/B testing) are a common source of performance regressions because they're outside your control.

- Load non-critical scripts with `async` or `defer` to avoid blocking parsing
- Use `lazyOnload` or equivalent strategy for analytics and ads
- Use the **facade pattern** for heavy embeds (YouTube, maps, chat): show a lightweight screenshot or thumbnail, load the real embed only when the user clicks

---

## Performance Budgets

Define measurable targets before building. Example budget:
- LCP: < 2.5s
- INP: < 100ms
- CLS: < 0.1
- Total blocking time: < 300ms
- JavaScript bundle (initial load): < 200KB gzipped
- Lighthouse performance score: > 90

Enforce budgets in CI with Lighthouse CI so regressions are caught before they reach production.

---

## Monitoring & Measurement

### Lab vs field data

**Lab** (Lighthouse, WebPageTest): synthetic, reproducible, run in CI. Good for catching regressions. Not representative of real users.

**Field** (Real User Monitoring): actual user experience. Use the Web Vitals library to capture CLS/FID/LCP/TTFB from real sessions and send to your analytics or APM tool.

Use both: lab for prevention, field for diagnosis.

### Profiling workflow

1. Identify the metric that's failing (LCP, INP, bundle size) from field data or Lighthouse
2. Use browser DevTools (Performance tab, Network tab) to reproduce and locate the bottleneck
3. Check Coverage tab for unused CSS/JS
4. Check Network tab for render-blocking resources and unoptimized images
5. Measure before and after any change

---

## Performance Checklist

Before marking frontend performance work complete:

- [ ] Core Web Vitals meet targets (LCP <2.5s, INP <100ms, CLS <0.1)
- [ ] Images: modern format, explicit dimensions, lazy loaded (except LCP image)
- [ ] JavaScript bundle analyzed — no unexpectedly large dependencies
- [ ] Code splitting applied to routes and heavy components
- [ ] Non-critical scripts loaded asynchronously
- [ ] Critical CSS inlined; non-critical CSS deferred
- [ ] Performance budgets defined and enforced in CI
- [ ] Real user monitoring in place
- [ ] Tested on real mobile devices and throttled networks (3G)
- [ ] Cache headers correct for static assets

---

## Common Pitfalls

**Optimizing without measuring** — always profile first; guess-and-check wastes time.

**Over-optimizing images** — 85% quality JPEG is visually identical to lossless for most content.

**Ignoring mobile** — desktop Lighthouse scores can be misleading; test on real low-end devices.

**Eager-loading everything** — lazy loading below-fold content is the single highest-ROI optimization in most apps.

**Third-party script accumulation** — every analytics tag, chat widget, and A/B test tool adds to TBT; audit these regularly.

---

## When to Escalate

Ask for human review when:
- Core Web Vitals consistently fail despite targeted optimizations
- Bundle size exceeds budget but all dependencies appear necessary
- Performance issues only reproduce in production, not locally
- Trade-offs between performance and required functionality are unclear
- Third-party scripts are required but severely impact metrics

---

## Framework-Specific Notes

> These sections show how the principles above apply in specific frameworks. The principles are the same; syntax differs.

### Next.js (React/TypeScript)

```javascript
// Dynamic imports for code splitting
import dynamic from 'next/dynamic'
const HeavyChart = dynamic(() => import('./HeavyChart'), {
  loading: () => <Spinner />,
  ssr: false
})

// Optimized images — handles format, sizing, lazy load automatically
import Image from 'next/image'
<Image src="/hero.jpg" alt="Hero" width={1200} height={600} priority />    // LCP image
<Image src="/card.jpg" alt="Card" width={400} height={300} />              // lazy by default

// Font optimization
import { Inter } from 'next/font/google'
const inter = Inter({ subsets: ['latin'] })

// ISR — page-level caching
export const revalidate = 3600

// Non-critical third-party scripts
import Script from 'next/script'
<Script src="https://www.googletagmanager.com/gtag/js" strategy="lazyOnload" />

// Bundle analyzer
// ANALYZE=true npm run build  (requires @next/bundle-analyzer in next.config.js)

// Performance budgets in next.config.js
module.exports = {
  images: { formats: ['image/avif', 'image/webp'] },
  experimental: { optimizeCss: true }
}
```

### Astro

```javascript
// Islands architecture — ship zero JS by default, hydrate only what's interactive
---
import HeavyComponent from './HeavyComponent.jsx'
---
<HeavyComponent client:visible />   // Hydrate when visible
<HeavyComponent client:idle />      // Hydrate during browser idle time
<HeavyComponent client:load />      // Hydrate immediately (use sparingly)

// Optimized images
import { Image } from 'astro:assets'
<Image src={import('./photo.jpg')} alt="Photo" width={800} height={600} />

// Static generation by default — no JS shipped unless a component opts in
```

### Lighthouse CI (any framework)

```yaml
# .github/workflows/lighthouse.yml
name: Lighthouse CI
on: [pull_request]
jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm install && npm run build
      - run: npm install -g @lhci/cli
      - run: lhci autorun
```

```json
// .lighthouserc.json
{
  "ci": {
    "assert": {
      "assertions": {
        "largest-contentful-paint": ["error", {"maxNumericValue": 2500}],
        "cumulative-layout-shift": ["error", {"maxNumericValue": 0.1}],
        "total-blocking-time": ["error", {"maxNumericValue": 300}],
        "categories:performance": ["error", {"minScore": 0.9}]
      }
    }
  }
}
```

---

## Further Reading

- [web.dev/performance](https://web.dev/performance/) — Google's authoritative frontend performance guide
- [Core Web Vitals](https://web.dev/vitals/) — official metric definitions and thresholds
- [web-vitals library](https://github.com/GoogleChrome/web-vitals) — measure CWV in real users' browsers
- [Lighthouse CI](https://github.com/GoogleChrome/lighthouse-ci) — automate performance budgets
- [Bundlephobia](https://bundlephobia.com/) — check package size before installing
- [High Performance Browser Networking](https://hpbn.co/) — networking fundamentals
