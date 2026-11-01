# Design System: Goers Resto Index

> Single source of truth for every screen in this interface. Generated with the
> `stitch-design-taste` skill and implemented in `frontend/src/index.css`.

## 1. Visual Theme & Atmosphere

An editorial dining ledger: warm paper, ink, hairlines, and one restrained ember
accent. The interface reads like a printed city index that happens to be live —
dense enough for 52 kitchens, airy enough to feel considered. Density sits at 6
(balanced daily app), variance at 7 (asymmetric split hero, no centered stacks),
motion at 4 (quiet, transform-only, no choreography for its own sake).

The atmosphere is a quiet San Francisco dining room at 5pm: linen, low light,
sharp typography, nothing shouting. No glass, no glow, no gradient text.

## 2. Color Palette & Roles

- **Paper Canvas** (`#F5F2EC`) — Page background, the default surface
- **Raised Paper** (`#FBF9F5`) — Sheets, sticky header, inset panels
- **Sunken Paper** (`#EDE9E1`) — Skeletons, inactive tracks, quiet fills
- **Ink** (`#1B1A17`) — Primary text, active fills, structural rules
- **Soft Ink** (`#4A463F`) — Secondary headings, body text on paper
- **Muted Stone** (`#6F6A61`) — Labels, metadata, helper copy
- **Faint Stone** (`#9B958A`) — Disabled states, decorative marks
- **Hairline** (`#DAD4C8`) — Table rules, dividers, input borders
- **Strong Hairline** (`#C5BDAE`) — Focusable field borders, tag outlines
- **Ember Clay** (`#B1502A`) — The only accent: primary CTA, links, focus rings, stars
- **Deep Ember** (`#8A3A1C`) — Accent hover/active
- **Ember Wash** (`#F0E2D8`) — Focus halo, selected metadata tint
- **Moss** (`#4C6B4F`) — Functional "open now" indicator only
- **Moss Wash** (`#E6EDE3`) — Open-state background tint

Rules: one accent only, saturation 61% (under the 80% ceiling). No purple, no
neon, no pure black, no second brand color. Moss exists as a semantic status
color, never as decoration. Do not introduce warm/cool gray drift — every neutral
above is warm stone.

## 3. Typography Rules

- **Display / UI Sans:** `Geist` (300/400/500/600)
  - Headlines: weight 500, `letter-spacing: -0.045em`, `line-height: 0.94`
  - Hierarchy comes from scale, weight, and ink value — not from six weights
- **Mono:** `Geist Mono` (400/500)
  - Every number, ID, time, count, label, and metadata string
  - Uppercase labels: `0.62–0.7rem`, `letter-spacing: 0.14em`
- **Body:** `Geist` 400, `0.95–1.02rem`, `line-height: 1.6`, max 62ch
- **Banned:** Inter, generic serifs (Times, Georgia, Garamond, Palatino), any
  serif in this dashboard/software context. No font pairing beyond Geist +
  Geist Mono.

## 4. Component Stylings

- **Buttons:** 2px radius, flat fills, 44px min height. Primary is Ember Clay.
  Secondary is transparent with a strong hairline. Press state translates down
  1px — tactile, never a zoom or glow. Focus ring is a 2px Ember outline with 2px
  offset.
- **Tags / Presets:** Rectangular, mono uppercase, 1px hairline. Active state is
  Ink fill with Paper text. Hover lifts 1px and darkens the border.
- **Restaurant rows:** No cards. A 6-column ledger row separated by 1px
  hairlines. Hover reveals an inset 2px Ember rule on the left edge. Row index in
  mono, name in Geist 500, cuisine as a mono micro-label.
- **Stats strip:** Four metrics on a single hairline rail with vertical dividers;
  never four floating cards.
- **Inputs:** Label above, control below. The search field is an underline input
  with a leading icon; date and time are underline inputs. Focus turns the
  underline Ember. No floating labels, no glowing fields.
- **Sheets:** Add/edit and weekly-hours flows open as a right-hand sheet (560px)
  with a hairline edge and a soft directional shadow. On mobile the sheet becomes
  full width.
- **Loaders:** Six skeleton rows matching the ledger grid, opacity pulse only. No
  circular spinners.
- **Empty state:** Composed hairline block with an eyebrow, a direct sentence, and
  one reset action. No emoji, no illustration clichés.
- **Toasts:** Ink bar, 4px Ember edge, bottom-left, slide-up, auto-dismiss.
- **Admin gate:** A read-only statement on the left, a hairline-separated sign-in form on the
  right. The form reuses the standard input styling; a wrong credential renders the inline
  Ember error block. Below 860px the two columns stack and the divider moves to the top.
- **Request failure:** A full-width block with a 1px Ember top rule, an eyebrow, a direct
  sentence naming the cause (backend down, HTTP status), and one retry action. Never a toast
  alone, and never a blank list.

## 5. Layout Principles

- Max width `1360px`, gutters `clamp(1.25rem, 4vw, 3rem)`.
- Hero is a 1.55 : 0.75 asymmetric split, bottom-aligned; it collapses to one
  column below 860px.
- Structural sections are separated by full-width hairlines, not nested cards.
  There is no card-inside-card pattern anywhere.
- CSS Grid only. No `calc()` percentage math, no flexbox width hacks.
- Every section has its own spatial zone; nothing overlaps or is absolutely
  stacked over text.
- The inline hero image lives inside the headline at type height with a pill
  radius, and drops below the headline on mobile.

## 6. Motion & Interaction

- Default easing `cubic-bezier(0.16, 1, 0.3, 1)`, 180–420ms. No linear easing.
- Ledger rows reveal with a staggered `translateY(10px)` + opacity cascade
  (`28ms` per row, capped).
- The live "open" dot runs a slow scale/opacity pulse (2.6s) — the only perpetual
  loop.
- Sheets slide in via `transform: translateX(100%)`; overlays fade.
- Only `transform` and `opacity` are animated. Widths, heights, and positions are
  never tweened.
- `prefers-reduced-motion: reduce` disables every animation and transition.

## 7. Responsive Rules

- Below 860px: hero stacks, query fields go 2-up, then 1-up below 640px.
- Below 760px: ledger rows stack into a three-line record; the admin table keeps
  its own horizontal scroll container (never the page).
- Stats strip becomes 2×2, with correct hairline placement.
- Headline scales with `clamp()`. Body never below `1rem`.
- All interactive targets are at least 44px tall on touch.
- No horizontal page scroll at any width.

## 8. Anti-Patterns (Banned)

- No emojis anywhere — including favicons, empty states, and toasts.
- No Inter, no generic serif, no third font family.
- No pure black (`#000`), no neon or outer-glow shadows, no gradient text.
- No purple/indigo/blue accent, no glassmorphism, no blurred translucent cards.
- No 3-column (or 4-card) equal grid of feature/stat cards.
- No centered hero, no card-inside-card nesting.
- No custom mouse cursors, no parallax, no scroll-jacking.
- No filler copy: "Scroll to explore", "Seamless", "Elevate", "Unleash",
  "Next-Gen", "Smart engine".
- No fake precision (`99.99%`), no generic placeholder names.
- No broken image links: local assets or `picsum.photos` only.
- No overlapping text and imagery; each element owns its space.
