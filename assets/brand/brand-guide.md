# Pilimon Bulletin brand guide

## Logo intent

The primary symbol retains the Monochrome Version 1 concept: a bold geometric uppercase P with a compact document motif built into the interior. The full wordmark keeps the same hierarchy as the approved reference: strong PILIMON lettering with smaller BULLETIN lettering beneath it, letter-spaced for clarity and balance.

## Core palette

- Primary brand: `#183B70` — main accent for links, active filters, CTAs, and focus states.
- Primary strong: `#102D56` — hover and pressed primary states.
- Neutral ink: `#101828` — symbol and wordmark on light surfaces.
- Page background: `#F4F6F8` — light app background.
- Surface: `#FFFFFF` — cards, panels, and search surfaces.
- Muted surface: `#EEF2F6` — secondary panels and quiet controls.
- Border: `#DCE5EE` — subtle separators and controls.
- Text primary: `#1E2A36` — main interface text.
- Text secondary: `#586875` — metadata and supporting labels.

## Dark-theme palette

- Primary brand: `#8BB3FF` — dark-mode accent for primary actions and focus states.
- Primary strong: `#C9D9FF` — dark-mode hover and active emphasis.
- Neutral ink: `#F8FAFC` — white wordmark and symbol on dark surfaces.
- Page background: `#101821` — dark app background.
- Surface: `#17222B` — elevated panels.
- Muted surface: `#202F3B` — secondary panels and quiet controls.
- Border: `rgba(163,181,203,0.2)` — subtle separators.
- Text primary: `#EDF2F8` — primary text in dark mode.

Semantic success and danger colors are reserved for status messages and destructive or urgent states; they are not brand colors. All standard interface accents use the blue and neutral tokens above.

## Logo assets and usage

- `assets/brand/p-symbol.svg` — standalone P symbol for compact use and app icon styling.
- `assets/brand/favicon.svg` — simplified browser-tab mark using the P icon.
- `assets/brand/pilimon-bulletin-wordmark-light.svg` — horizontal lockup for light backgrounds.
- `assets/brand/pilimon-bulletin-wordmark-dark.svg` — horizontal lockup for dark backgrounds.
- `assets/brand/pilimon-bulletin-stacked-light.svg` — stacked debug-friendly lockup for light surfaces.
- `assets/brand/pilimon-bulletin-stacked-dark.svg` — stacked lockup for dark surfaces.

The logo remains monochrome by default, while the rest of the interface uses a restrained accent palette for usability and brand consistency.

## Icon conventions

- Use SVG strokes and fills with `currentColor` wherever possible.
- Keep stroke weight consistent across search, filter, archive, time, and utility icons.
- Use theme tokens instead of fixed black/white colors for live UI controls.
- Maintain strong contrast in both light and dark modes.
- Keep icon edges minimal and geometric.
