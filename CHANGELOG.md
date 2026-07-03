# Changelog

All notable changes to TN Pallet are recorded here.

## 0.1.6 - 2026-07-03

- Added a native WordPress Help tab with copy/paste examples for generated palette utility classes.

## 0.1.5 - 2026-07-03

- Reissued the update-check and native open colour picker fixes with a fresh version bump for manual update clarity.

## 0.1.4 - 2026-07-03

- Made the plugin row "Check for updates" action explicitly persist TN Pallet update data after a forced GitHub release check.
- Added admin notices for manual update check results.
- Restored the native WordPress colour picker layout inside palette cards while keeping pickers open by default.

## 0.1.3 - 2026-07-03

- Changed the palette admin screen from a table to responsive colour cards.
- Kept colour pickers open by default to avoid layout jumps while editing.
- Replaced the remove text link with a trash icon button.

## 0.1.2 - 2026-07-03

- Added WordPress's native `update_plugins_github.com` update URI filter path for GitHub release checks.

## 0.1.1 - 2026-07-03

- Removed manual colour value and preview columns from the palette admin screen.
- Removed manual ordering controls and sorted palette colours alphabetically by name.
- Changed the generated CSS directory to `wp-content/uploads/tn-pallet/`.
- Renamed the palette option and admin menu slug to use TN Pallet naming.

## 0.1.0 - 2026-07-03

- Added the initial WordPress palette admin screen.
- Added JSON option storage and generated utility CSS.
- Added front-end, admin, and block editor stylesheet enqueueing.
- Added GitHub release updater support.
- Added release ZIP build tooling.
