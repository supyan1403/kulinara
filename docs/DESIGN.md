---
name: Culinary Modernity
colors:
  surface: '#faf9f6'
  surface-dim: '#dbdad7'
  surface-bright: '#faf9f6'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f3f0'
  surface-container: '#efeeeb'
  surface-container-high: '#e9e8e5'
  surface-container-highest: '#e3e2e0'
  on-surface: '#1a1c1a'
  on-surface-variant: '#404945'
  inverse-surface: '#2f312f'
  inverse-on-surface: '#f2f1ee'
  outline: '#707974'
  outline-variant: '#bfc9c3'
  surface-tint: '#2b6956'
  primary: '#003428'
  on-primary: '#ffffff'
  primary-container: '#044d3c'
  on-primary-container: '#7fbda7'
  inverse-primary: '#95d3bc'
  secondary: '#446556'
  on-secondary: '#ffffff'
  secondary-container: '#c5ebd7'
  on-secondary-container: '#496b5c'
  tertiary: '#591403'
  on-tertiary: '#ffffff'
  tertiary-container: '#772a15'
  on-tertiary-container: '#ff9377'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#b0f0d8'
  primary-fixed-dim: '#95d3bc'
  on-primary-fixed: '#002118'
  on-primary-fixed-variant: '#0b503f'
  secondary-fixed: '#c5ebd7'
  secondary-fixed-dim: '#aacfbc'
  on-secondary-fixed: '#002115'
  on-secondary-fixed-variant: '#2c4d3f'
  tertiary-fixed: '#ffdbd2'
  tertiary-fixed-dim: '#ffb4a1'
  on-tertiary-fixed: '#3c0800'
  on-tertiary-fixed-variant: '#7c2e19'
  background: '#faf9f6'
  on-background: '#1a1c1a'
  surface-variant: '#e3e2e0'
typography:
  display:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.03em
  display-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.025em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.03em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style
The design system embodies an artisanal culinary perspective grounded in modern digital ergonomics. Its core personality is natural, vibrant, disciplined, and inviting. Rather than leaning on classical, heritage serif conventions that evoke rigid fine dining, the design system adopts a contemporary, warm-minimalist aesthetic tailored for effortless everyday interaction.

The UI must evoke the freshness of organic market ingredients, precision culinary execution, and absolute clarity. Layouts leverage generous whitespace, soft cream canvases, balanced structural framing, and vibrant food-forward focal points. Surfaces feel organic, calm, and deliberate—eschewing excessive skeumorphism in favor of crisp visual hierarchy, restrained depth, and friendly, accessible modern typography.

## Colors
The palette pairs deep botanical earth tones with luminous cream grounds and appetizing warm highlights:

- **Primary (`#044D3C`) — Deep Forest Green:** Anchors brand authority, high-contrast actions, primary navigation states, and pivotal headline emphasis. Evokes farm-to-table integrity and dense botanical freshness.
- **Secondary (`#6B8E7D`) — Muted Sage:** Serves as supportive structural coloration, subtle category indicators, active state highlights, and low-contrast borders.
- **Tertiary (`#E07A5F`) — Warm Terracotta / Apricot:** An appetizing accent reserved for callouts, dietary alerts, high-conversion micro-actions, timers, and nutritional emphasis.
- **Neutral (`#F9F8F5`) — Clean Cream Tint:** The core surface foundation, eliminating clinical stark white in favor of soft, natural canvas warmth.

Supporting contextual surfaces utilize:
- **Surface Muted (`#F0EFEA`):** Recessed containers, table headers, and inactive inputs.
- **Text Primary (`#13241E`):** A deep charcoal-forest tint providing maximum legibility without the harshness of pure black.
- **Text Secondary (`#566A61`):** Neutralized sage-slate for supporting metadata and secondary labels.

## Typography
Typography transitions completely away from editorial serifs into a balanced, highly legible modern sans-serif pairing. 

**Plus Jakarta Sans** governs all headings, metric callouts, and navigational interactive labels. Its subtly rounded geometric terminals bring an accessible, hospitable human touch to the interface while retaining architectural crispness.

**Inter** handles sustained body reading, ingredient manifests, step-by-step cooking instructions, and metadata. Its tall x-height and mechanical neutrality ensure flawless legibility across compact viewports, dense nutritional grids, and glanceable metric readouts.

Track display sizes tighter (-0.02em to -0.03em) to ensure titles remain visually compact and modern. Small badge labels utilize slight positive tracking (+0.02em to +0.03em) for quick scanning in recipe cards.

## Layout & Spacing
The layout model follows a structured, responsive 12-column grid system on desktop (collapsing to 8 columns on tablet and 4 columns on mobile viewports).

- **Grid Alignment:** Grid max-width is constrained to `1280px` centered horizontally to avoid excessive eye travel on wide screens.
- **Rhythm & Padding:** Spacing operates strictly on an 8pt spatial grid (with 4pt sub-increments for compact badge and tag alignments). 
- **Adaptive Breakpoints:**
  - *Mobile (< 640px):* Margin is reduced to `1rem` with `1rem` gutters. Recipe and ingredient grids collapse to single columns or side-scrolling carousels.
  - *Tablet (640px - 1024px):* Margins scale to `1.5rem`; 2-column recipe card structures with balanced aspect ratios.
  - *Desktop (> 1024px):* Full `2rem` margin with `1.5rem` gutters, enabling 3- to 4-column item grids and sticky nutritional sidebar panes.

## Elevation & Depth
Depth relies primarily on **tonal layers and ambient organic shadows** rather than high-contrast structural borders or synthetic glass effects.

- **Surface Tiers:** Clean cream (`#F9F8F5`) acts as the canvas. Pure white (`#FFFFFF`) containers float slightly above to group high-priority recipe content, dietary tables, and inputs.
- **Shadow Profile:** Shadows are extra-diffused, ultra-low opacity, and warm-tinted with forest undertones to feel physical yet weightless:
  - *Resting Card:* `0 2px 8px -2px rgba(4, 77, 60, 0.04), 0 1px 3px 0 rgba(19, 36, 30, 0.02)`
  - *Hovered / Interactive:* `0 12px 24px -6px rgba(4, 77, 60, 0.08), 0 4px 8px -2px rgba(19, 36, 30, 0.03)`
  - *Modal / Drawer:* `0 24px 48px -12px rgba(4, 77, 60, 0.16)`
- **Borders:** When outlines are required for containment (such as nutrition matrices or inputs), use low-contrast soft sage lines (`#E2E8E4`) at `1px` thickness.

## Shapes
Shapes express a warm, ergonomic geometry using standard `0.5rem` (8px) base rounding, scaling to `1rem` (16px) on cards, inputs, and modals, and full pill contours on select micro-components:

- **Cards & Sheets:** Utilize `rounded-lg` (16px) to maintain a soft, approachable frame for food photography and ingredient lists.
- **Buttons & Form Controls:** Standardize on `rounded` (8px) for balanced modern discipline and tap target clarity.
- **Chips, Pills & Badges:** Use full circular radius (`9999px`) for dietary labels (e.g., Gluten-Free, Vegan, High-Protein) to differentiate metadata tags from functional cards.

## Components

### Buttons
- **Primary:** Forest green (`#044D3C`) fill with pure white typography (`Plus Jakarta Sans`, `label-lg`). Hover shifts to a deepened spruce tone (`#033B2E`) with a gentle 2px elevation rise.
- **Secondary:** Tonal cream/sage background (`#EAECE7`) with primary text (`#044D3C`). No harsh stroke.
- **Tertiary / Ghost:** Transparent surface with muted forest typography (`#044D3C`), highlighting underline on hover.
- **Size Scale:** Minimum touch target height of 44px on mobile, 40px on desktop, with `1.25rem` horizontal padding.

### Recipe Cards
- Pure white container resting on natural cream canvas with soft ambient shadow.
- Image container features 16:9 or 1:1 aspect ratios, nested with `12px` radius and top placement.
- Clean typography lockup: prep time and difficulty in muted sage (`label-md`), followed by recipe title in `headline-sm`.
- Nutrition pill chips anchored along card footer with crisp 8px spacing.

### Nutrition Badges & Chips
- **Dietary Badges:** Pill-shaped, semi-soft fill (e.g., warm terracotta tint `#FBF0EC` for highlight stats; soft sage `#EDF3F0` for macro balance).
- Text styled in `label-sm` with subtle letter spacing (+0.03em).
- **Macro Badges (Protein, Carbs, Fat):** Monoline minimal dividers inside badges to present crisp numeric data and units side by side.

### Form Inputs & Search
- Solid white surface framed by 1px subtle border (`#DDE4DF`). Focus ring transitions to a soft 2px forest green halo (`#044D3C` at 20% opacity) without shifting layout geometry.
- Placeholder copy rendered in `Inter` body-md in `#7A8E85`.

### Checkboxes & Segmented Controls
- Rounded square (4px) checkboxes in `#044D3C` active fill with crisp white check mark icon.
- Recipe step checkboxes strike through instructions using a soft sage tint when completed.
- Segmented recipe toggles (e.g., Servings: 2 / 4 / 6) feature a sliding white pill on a muted cream background (`#F0EFEA`).