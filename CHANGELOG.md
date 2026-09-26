# Changelog

All notable changes to TN Pallet are recorded here.

## 0.1.14 - 2026-09-26

- Lower the PHP requirement to 7.4, matching WordPress 7.0.
- Allow the PHP 7.4-compatible TN Update Controller bootstrap.

## 0.1.13 - 2026-09-26

- Require WordPress 7.0+ and PHP 8.5+ for this release.

- Replace the independent GitHub updater with the version 1 TN Update Controller integration.
- Add local Install/Activate/Check controller actions and standardise Techn author/repository metadata.
- Preserve plugin identity, feature code, settings and activation scope; no feature-plugin release discovery runs during page rendering.

## 0.1.12 - 2026-08-23

- Fixed generated palette stylesheet URLs using HTTP on HTTPS production pages behind a reverse proxy, which caused browsers to block `palette.css` as mixed content.
- Preserved the restored v0.1.8 asset hooks while normalising only the public stylesheet URL scheme.

## 0.1.11 - 2026-08-23

- Restored the production asset hooks exactly to the last known-good 0.1.8 implementation: explicit front-end loading, explicit admin loading, and the established editor asset hook.
- Removed TN Pallet's direct editor-iframe integration; AS Local CSS now owns mirroring established front-end styles into that iframe.

## 0.1.10 - 2026-08-23

- Restored the dedicated front-end stylesheet enqueue while retaining block-editor iframe loading, so themes that suppress shared block assets still receive `palette.css`.
- Added a repository-controlled update manifest so WordPress can discover releases when the GitHub API returns a shared-host rate-limit or forbidden response.
- Removed the release-asset `HEAD` request from the public redirect fallback because signed GitHub asset URLs can reject `HEAD` while accepting the updater's normal `GET` download.

## 0.1.9 - 2026-08-23

- Loaded the generated palette stylesheet through `enqueue_block_assets` so its CSS custom properties and utility classes reach the block editor iframe as well as the front end.
- Stopped relying on the outer editor asset hook for canvas styling.
- Removed the plugin URI header so WordPress shows the updater's GitHub and update-check actions without a redundant "Visit plugin site" link.

## 0.1.8 - 2026-07-03

- Fixed manual update checks so WordPress renders the plugin update row after a successful GitHub release lookup.
- Normalised GitHub updater payloads for both WordPress's native Update URI API and classic plugin update rows.
- Removed WordPress's default editor colour palette from the block editor colour picker.

## 0.1.7 - 2026-07-03

- Registered the saved palette as the WordPress block editor colour palette.
- Replaced theme colour palettes with the TN Pallet palette for classic, hybrid, and block themes.
- Added an "Allow custom editor colours" setting.
- Cleared editor palette/theme JSON caches after palette saves and CSS regeneration.

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
