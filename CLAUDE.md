# Working on Snap

See [ARCHITECTURE.md](ARCHITECTURE.md) for how the theme is put together.

## Accessibility: WCAG 2.1 AA is required

Every change to templates, SCSS, PHP output or AMD JavaScript must meet WCAG 2.1 Level AA. Treat a failure as a bug in the change, not as follow-up work. Before calling a UI change done, check it against the list below and say which checks you ran.

### Checklist

- **Contrast (1.4.3, 1.4.11).** Text needs 4.5:1 against its background, or 3:1 for large text (24px, or 18.66px bold). Focus indicators, form control borders, icons that carry meaning and other UI parts need 3:1. Check in light mode, in dark mode, and against the colours admins can set (theme brand colour, nav bar colours). PHP code that picks colours should use `\theme_snap\color_contrast`.
- **Colour alone (1.4.1).** Don't use colour as the only way to show state, errors, links in body text, or required fields. Add text, an icon with a text alternative, or an underline.
- **Keyboard (2.1.1, 2.1.2, 2.4.3).** Everything clickable must work with the keyboard: Tab to reach it, Enter or Space to use it, Escape to close dialogs, drawers and menus. Focus order follows the visual order. No keyboard traps. When a drawer or dialog closes, focus goes back to the control that opened it.
- **Visible focus (2.4.7).** Every focusable element shows a clear `:focus-visible` outline with 3:1 contrast. Never remove an outline without putting an equally visible one in its place.
- **Semantics and names (1.3.1, 4.1.2).** Use native elements (`<button>`, `<a href>`, `<label>`, headings, lists, tables with `<th>`) before ARIA. Every control has an accessible name. Icon-only buttons get a visually hidden label (`sr-only` / `visually-hidden`) or `aria-label`, and their icon gets `aria-hidden="true"`. Toggles keep `aria-expanded` or `aria-pressed` up to date.
- **Headings and landmarks (1.3.1, 2.4.6).** Heading levels must not skip. Keep the existing `<main>`, `<nav>` and region landmarks, and label repeated landmarks.
- **Text alternatives (1.1.1).** Images that carry information need meaningful `alt` text. Decorative images get `alt=""`. All user-facing strings go in `lang/en/theme_snap.php`, including hidden labels.
- **Reflow and zoom (1.4.4, 1.4.10, 1.4.12).** Content must work at 320 CSS px wide and at 200% zoom with no horizontal scrolling (data tables are the exception), and with increased text spacing. Use `rem`/`em` for font sizes. Don't fix the height of boxes that contain text. Sticky or fixed elements must not cover content or the focused element (2.4.11 is 2.2 guidance, but follow it).
- **Motion and timing (2.2.2, 2.3.1).** Animations respect `prefers-reduced-motion`. Anything that moves on its own for more than 5 seconds can be paused. Nothing flashes.
- **Status messages (4.1.3).** Content that updates via AJAX (saving, filtering, counts, errors) is announced through an `aria-live` region or `core/notification`, without moving focus.
- **Forms (3.3.1, 3.3.2).** Inputs have visible labels. Errors are given in text, linked to their field, and say how to fix them.
- **Pointer (2.5.3).** The visible label text is included in the accessible name.

### Testing

- Tag Behat scenarios for new or changed UI with `@accessibility` and add `And the page should meet accessibility standards` (axe). See `tests/behat/accessibility_*.feature` for examples.
- Do a manual keyboard pass: Tab through the changed area, then open and close everything in it.
- Look at the page at 320px width and at 200% zoom, in both light and dark colour modes.
- Take before/after screenshots that should also show the keyboard focus state when the change touches interactive elements.
- Keep screenshots of changes in separate folders, one per commit, rename folder with commit reference after successful implementation.

## Dark mode

Snap uses Moodle's own colour modes (Boost's "Enable colour modes"): `[data-bs-theme="dark"]` on `<html>`. Every UI change needs a light and a dark check.

- Put a dark rule next to the light rule it changes, in the same partial: Boost's `dark-mode` mixin inside the rule, or a `[data-bs-theme="dark"] { … }` block straight after it. At the top level the mixin needs a parent selector, so write the block. Don't use Bootstrap's `color-mode()` mixin: it nests the attribute the wrong way round.
- Only patches for the styles of Moodle's own components (question types, quiz, assignment and so on) go in `_darkmode.scss`, in that component's section, which names the files it patches.
- Use what's there before adding colours: Bootstrap's variables (`var(--bs-body-bg)`, `--bs-tertiary-bg`, `--bs-border-color`, `--bs-secondary-color`), `$dark-surface-page` and `$dark-surface-raised`, `dark-readable($colour)` for a light version of a colour, and the status tints `$snap-dark-success-bg`, `-warning-bg`, `-danger-bg` and `-info-bg` in `_mixins.scss`.
- Brand and category colours used as text, border or outline colours get their dark version automatically from `theme_snap_add_dark_brand_colors()` in `lib.php`, so don't add dark rules for those. Backgrounds and shadows in the brand colour aren't covered.
- Don't put attribute selectors inside `:is()` or `:has()`. The SCSS compiler fails on them and pages fall back to Boost's CSS. Write the selectors out in full.
