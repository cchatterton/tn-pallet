# TN Pallet 0.2.0 validation

Date: 10 October 2026 (Australia/Sydney).

## Compatibility and data

WordPress 7.1.3 was exercised on PHP 7.4.30, 8.1.23 and 8.5.7, using disposable MySQL and SQLite installations. No customer site was modified. Plugin files also pass PHP 7.4 syntax validation and the JavaScript syntax check.

The palette integration suite covers existing custom values, defaults only for empty storage, first-hyphen grouping, unchanged flat option/CSS output after a round trip, editor slugs, label changes, legacy import, duplicate and invalid input, bounded JSON, concurrent saves, previous JSON, failed CSS publication, REST capabilities/nonces, AI merging, conditional UI and separate Help tabs. Controller integration checks assert one GitHub link and zero update-metadata HTTP while reading plugin rows/transients.

The AI suite uses the real WordPress AI Client and official OpenAI provider 1.2.0, with HTTP intercepted by deterministic fixtures. It checks capability detection without generation, image inclusion, automatic persistence, unreturned-colour retention, attachment JSON, invalid JSON, provider errors and missing media. It makes no paid AI requests.

## Interface

Author Branded mode uses Techn identity and the navy/orange tokens from the Branding and UX standard. WordPress native colour pickers, Media Library, Help tabs, notices and admin navigation are retained. Authored CSS is scoped and uses rem dimensions.

Browser checks: 1440×1000 desktop, 390×844 narrow viewport and the app's approximately 585-pixel-wide default. Native notices appear above the hero. Palette rows do not wrap or overflow the page; their contents scroll horizontally. Tested default populated state, adding a three-card section, adding a fourth card, save/loading/success, JSON invalid-input error, previous-JSON recovery, configured/unconfigured connector visibility, Media Library selection, interpretation/save and last-response JSON display.

Accessibility checks: keyboard Left/Right tab selection, aria-selected/tabindex state, visible focus, labelled colour hex inputs, section labels, named remove buttons, focusable scroll regions, live save feedback and error focus. Native colour-picker hex fields provide keyboard editing independently of the visual spectrum. No full screen-reader audit was performed.

Screenshots are in qa/ and excluded from the distributable ZIP.

## Limitations

Minimum WordPress 7.0 and multisite were not exercised in this feature validation. External provider credentials and live image interpretation quality were not tested; connector requests used fixtures. Other provider implementations may have their own image limits or model requirements. Browser zoom beyond ordinary sizing was not exhaustively tested. Filesystem and database failures beyond the covered write failure paths remain host-dependent.

## Published upgrade verification

Published v0.2.0 ZIP matches the committed source and root ZIP byte-for-byte (SHA-256 `8d852b01ef29a7d018636c2c9b8ee469f1259faad05b2d145c02e53f21b2773a`). The public TN Update Controller catalogue was fetched with a unique query and verified for version, tag, plugin basename, asset and checksum.

On disposable WordPress 7.1.3 / PHP 7.4.30, the controller's explicit check displayed native 0.2.0 update metadata and View details. Its checksum-verified WordPress upgrader installed the published ZIP over official 0.1.14. Reloading confirmed preserved custom palette values and names, byte-identical CSS and flat JSON, unchanged editor preference and activation state. The new section editor's save preserved the same palette. Plugin-row/transient reads with the active controller performed zero update-metadata HTTP.

Final automated results: 43 palette assertions and 15 AI assertions on each of PHP 7.4.30, 8.1.23 and 8.5.7, plus controller integration and syntax checks.
