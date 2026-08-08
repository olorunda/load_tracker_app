---
name: Fleet Precision System
colors:
  surface: '#f9f9ff'
  surface-dim: '#cfdaf2'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f3ff'
  surface-container: '#e7eeff'
  surface-container-high: '#dee8ff'
  surface-container-highest: '#d8e3fb'
  on-surface: '#111c2d'
  on-surface-variant: '#434655'
  inverse-surface: '#263143'
  inverse-on-surface: '#ecf1ff'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#505f76'
  on-secondary: '#ffffff'
  secondary-container: '#d0e1fb'
  on-secondary-container: '#54647a'
  tertiary: '#525657'
  on-tertiary: '#ffffff'
  tertiary-container: '#6b6e70'
  on-tertiary-container: '#eff1f3'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#d3e4fe'
  secondary-fixed-dim: '#b7c8e1'
  on-secondary-fixed: '#0b1c30'
  on-secondary-fixed-variant: '#38485d'
  tertiary-fixed: '#e0e3e5'
  tertiary-fixed-dim: '#c4c7c9'
  on-tertiary-fixed: '#191c1e'
  on-tertiary-fixed-variant: '#444749'
  background: '#f9f9ff'
  on-background: '#111c2d'
  surface-variant: '#d8e3fb'
typography:
  display:
    fontFamily: Inter
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  data-mono:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 16px
  margin: 24px
---

## Brand & Style

The design system is engineered for high-utility fleet management and asset tracking. The brand personality is professional, reliable, and operationally focused. It prioritizes clarity over decoration, ensuring that logistics managers can process high-density data without cognitive fatigue.

The design style follows a **Modern Minimalist** approach. It utilizes a vast amount of whitespace and a restrained color palette to direct attention to critical telemetry and status changes. Visual hierarchy is established through subtle tonal shifts and precise typography rather than heavy ornamentation. The interface should feel like a sophisticated instrument: quiet, dependable, and efficient.

## Colors

The palette is anchored by a clean white (`#FFFFFF`) and a very light cool gray (`#F8FAFC`) to differentiate background layers from container surfaces.

- **Primary Blue (#2563EB):** Reserved for primary actions, active navigation states, and key interactive markers on maps.
- **Slate Gray (#1E293B):** Used for primary headings and body text to ensure high contrast and professional tone.
- **Secondary Slate (#64748B):** Used for labels, secondary information, and icon outlines.
- **Status Indicators:** 
    - **Success Green:** Indicates 'Loaded' or 'In Transit'.
    - **Soft Gray:** Indicates 'Empty' or 'Stationary'.
    - **Amber/Red:** Reserved strictly for maintenance alerts, delays, or fuel warnings.

## Typography

This design system utilizes **Inter** across all levels to maintain a systematic and utilitarian feel. The typographic scale is optimized for data density.

- **Data Readability:** For tables and numerical telemetry, use the `data-mono` role which enables tabular figures (tnum) to ensure numbers align vertically for easy comparison.
- **Hierarchy:** Use `label-md` for table headers and small metadata tags. 
- **Scale:** On mobile devices, `display` and `headline-lg` should be reduced by 4px to maintain layout integrity.

## Layout & Spacing

The layout employs a **Fluid Grid** system with a focus on dashboard modularity. 

- **Desktop (1440px+):** 12-column grid with 16px gutters and 24px side margins. Sidebar navigation is fixed at 260px.
- **Tablet (768px - 1439px):** 8-column grid with 16px gutters. Sidebar collapses to an icon-only rail (64px).
- **Mobile (<767px):** 4-column grid with 12px gutters. Content stacks vertically.

Spacing follows a 4px baseline. Use `md (16px)` for standard padding within data cards and `lg (24px)` for section spacing. Data tables should use condensed vertical padding (8px) to maximize the amount of visible information per screen.

## Elevation & Depth

Visual depth in the design system is achieved through **Tonal Layering** and **Ambient Shadows**.

1. **Floor (Level 0):** The base background layer (`#F8FAFC`).
2. **Surface (Level 1):** Main content cards and containers (`#FFFFFF`). These use a very soft, diffused shadow: `0px 1px 3px rgba(0, 0, 0, 0.05), 0px 1px 2px rgba(0, 0, 0, 0.03)`.
3. **Overlay (Level 2):** Popovers, dropdowns, and tooltips. These use a more pronounced shadow to indicate interactivity: `0px 10px 15px -3px rgba(0, 0, 0, 0.1)`.

Avoid heavy borders; use 1px strokes in `#E2E8F0` to define boundaries only when necessary (e.g., between table rows).

## Shapes

The design system uses a **Rounded** shape language to soften the industrial nature of fleet data and make the interface feel modern and accessible.

- **Cards & Primary Containers:** 8px (`0.5rem`) corner radius.
- **Buttons & Input Fields:** 8px (`0.5rem`) corner radius.
- **Status Badges & Chips:** 16px (`1rem`) corner radius for a "pill" appearance, making them easily distinguishable from buttons.

## Components

### Data Cards
Cards are the primary vehicle for high-level metrics (e.g., "Active Vehicles"). They must feature a `label-md` title, a `headline-lg` value, and a small trend indicator or sparkline. 

### Status Badges
Pill-shaped indicators used for asset states. 
- **Loaded:** Green background (10% opacity) with Green text.
- **Empty:** Gray background (10% opacity) with Gray text.
- **Alert:** Amber or Red background (10% opacity) with corresponding text color.

### Dense Tables
Tables are the heart of the system. Rows should be 40px - 48px in height. Use zebra striping (alternate rows in `#F8FAFC`) only if the table exceeds 12 columns. Headers must be "sticky" during scroll.

### Input Fields
Inputs use a white background with a 1px `#E2E8F0` border. On focus, the border changes to the Primary Blue with a 3px soft blue glow (outline).

### Map Integrated UI
Floating action panels on maps should have a background blur (12px) and a slightly higher elevation shadow to ensure they remain legible over complex cartography. Use Primary Blue for vehicle markers and directional arrows.