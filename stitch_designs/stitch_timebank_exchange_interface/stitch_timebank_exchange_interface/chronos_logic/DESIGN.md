---
name: Chronos Logic
colors:
  surface: '#081425'
  surface-dim: '#081425'
  surface-bright: '#2f3a4c'
  surface-container-lowest: '#040e1f'
  surface-container-low: '#111c2d'
  surface-container: '#152031'
  surface-container-high: '#1f2a3c'
  surface-container-highest: '#2a3548'
  on-surface: '#d8e3fb'
  on-surface-variant: '#c6c6cd'
  inverse-surface: '#d8e3fb'
  inverse-on-surface: '#263143'
  outline: '#909097'
  outline-variant: '#45464d'
  surface-tint: '#bec6e0'
  primary: '#bec6e0'
  on-primary: '#283044'
  primary-container: '#0f172a'
  on-primary-container: '#798098'
  inverse-primary: '#565e74'
  secondary: '#5de6ff'
  on-secondary: '#00363e'
  secondary-container: '#00cbe6'
  on-secondary-container: '#00515d'
  tertiary: '#f9bd22'
  on-tertiary: '#402d00'
  tertiary-container: '#211600'
  on-tertiary-container: '#a47a00'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#dae2fd'
  primary-fixed-dim: '#bec6e0'
  on-primary-fixed: '#131b2e'
  on-primary-fixed-variant: '#3f465c'
  secondary-fixed: '#a2eeff'
  secondary-fixed-dim: '#2fd9f4'
  on-secondary-fixed: '#001f25'
  on-secondary-fixed-variant: '#004e5a'
  tertiary-fixed: '#ffdf9f'
  tertiary-fixed-dim: '#f9bd22'
  on-tertiary-fixed: '#261a00'
  on-tertiary-fixed-variant: '#5c4300'
  background: '#081425'
  on-background: '#d8e3fb'
  surface-variant: '#2a3548'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.02em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '500'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.5'
  label-caps:
    fontFamily: Geist
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1.0'
    letterSpacing: 0.1em
  mono-data:
    fontFamily: Geist
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.4'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 16px
  margin-mobile: 20px
  margin-desktop: 48px
  container-max: 1280px
---

## Brand & Style

The design system is built upon a foundation of **Modern Futurism** and **Premium Utility**. It moves away from standard social networking tropes, instead positioning the exchange of time as a high-value financial transaction. The brand personality is "The Architect"—precise, visionary, and deeply reliable. 

The aesthetic leverages **Glassmorphism** and **High-Contrast Boldness** to create a sense of depth and technical sophistication. By utilizing dark mode as the primary environment, the interface feels like a sophisticated dashboard or an advanced terminal, emphasizing that "time is the most valuable asset." All interactions should feel intentional, smooth, and calibrated, evoking an emotional response of security and forward-looking community connection.

## Colors

This design system utilizes a deep, monochromatic base to allow functional accents to shine.

- **Primary (Time Blue):** Used for deep backgrounds and primary structural layers.
- **Accent (Chrono Cyan):** Used for interactive states, primary buttons, and data visualizations. This color should be treated as a "light source" within the UI.
- **Secondary (Reputation Gold):** Reserved exclusively for status, trust indicators, and high-level achievements.
- **Backgrounds:** The primary background is `#020617` (Deepest Navy), with `#0F172A` and `#1E293B` used for layered surfaces and containers to create depth.
- **Functional:** `#10B981` (Contribution Green) is used for positive growth metrics and "transaction complete" states.

## Typography

The typographic scale emphasizes hierarchy through contrast in weight and tracking. 

- **Headlines:** Plus Jakarta Sans provides a friendly yet modern geometric feel. For high-level displays, use slightly wider tracking to increase the "premium" feel.
- **Body:** Inter is used for its exceptional legibility in dark environments, ensuring that dense exchange descriptions are easy to parse.
- **Technical/Labels:** Geist is introduced for data-heavy elements, monospaced for "time units" and "transaction IDs" to reinforce the futuristic, technical nature of the bank.

## Layout & Spacing

The layout follows a **Fixed Grid** model on desktop and a **Fluid Fluid** model on mobile. 

- **Grid:** A 12-column system is used for desktop (1280px max-width).
- **Rhythm:** An 8px base grid governs all component dimensions, while a 4px "micro-grid" handles internal spacing like icon-to-text distances.
- **Padding:** Containers should use generous internal padding (min 24px) to avoid visual clutter and maintain the premium, airy feel.
- **Mobile:** Elements reflow to a single column with a 20px horizontal safety margin.

## Elevation & Depth

Depth is achieved through **Glassmorphism** and **Tonal Layering** rather than traditional drop shadows.

- **Surface Tiers:** 
    - *Level 0:* Deepest Navy (#020617) - Global background.
    - *Level 1:* Time Blue (#0F172A) - Primary cards and navigation bars.
    - *Level 2:* Glass Layer - 40% opacity with 16px Backdrop Blur and a 1px inner stroke (White @ 10% opacity) to simulate a glass edge.
- **Glow Effects:** Instead of shadows, interactive elements use "Chrono Cyan" outer glows (8px-16px blur, 20% opacity) when hovered or active, suggesting an energized state.

## Shapes

The design uses **Rounded (Option 2)** shapes to balance technical precision with human approachability. 

- Standard components (Buttons, Inputs) use an 8px radius.
- Large containers and Glassmorphic cards use a 16px to 24px radius to feel softer and more modern.
- All "Currency Icons" (representing Time) should be perfectly circular to represent the infinite flow of time and community cycles.

## Components

- **Cards:** Utilize the Level 2 Glassmorphism tier. They must include a subtle 1px border (#FFFFFF10) to define edges against the dark background. 
- **Buttons:** 
    - *Primary:* Chrono Cyan background with black text. On hover, increase brightness and add a 12px cyan outer glow.
    - *Ghost:* Transparent with a 1px Cyan border.
- **Progress Bars:** Ultra-thin (4px height). The "track" is dark navy, while the "indicator" is a glowing Cyan gradient.
- **Inputs:** Darker than the background layer with a subtle bottom-border indicator that glows Cyan on focus.
- **Navigation:** The mobile bottom tab bar is a persistent glass element with a background blur. Active icons feature a small cyan dot or "glow" beneath them.
- **Skill Radars:** Use thin, low-opacity white lines for the grid and a solid Chrono Cyan fill at 20% opacity for the data area, with Reputation Gold markers for peak achievements.