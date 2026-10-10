# TN Pallet

TN Pallet is a lightweight WordPress plugin for managing a named colour palette from Appearance > Palette.

The plugin stores the complete palette as a JSON string in the `tnp_colour_palette` option, generates `palette.css` under `wp-content/uploads/tn-pallet/`, and enqueues that generated CSS on the front end, in admin, and inside the block editor canvas.

## GitHub Update Metadata

- GitHub owner: `cchatterton`
- GitHub repository: `tn-pallet`
- Plugin slug: `tn-pallet`
- Main plugin file: `tn-pallet/tn-pallet.php`
- Release ZIP asset name: `tn-pallet.zip`
- Author: `Techn`
- Author URL: `https://techn.com.au`
- Update URI: `https://github.com/cchatterton/tn-pallet`

## Build

Run:

```bash
scripts/build-plugin-zip.sh
```

The build writes `dist/tn-pallet.zip` and copies the same package to `tn-pallet.zip` at the repository root.

## Release Checklist

- Bump the plugin header version in `tn-pallet/tn-pallet.php`.
- Bump `TNP_VERSION` in `tn-pallet/tn-pallet.php`.
- Add release notes to `CHANGELOG.md`.
- Run syntax checks.
- Run `scripts/build-plugin-zip.sh`.
- Verify the ZIP contains `tn-pallet/tn-pallet.php` as the top-level plugin file.
- Create a GitHub release tag matching the plugin version, such as `v0.1.8`.
- Attach `tn-pallet.zip` to the release.

## Controller migration — 0.1.13

Updates are now supplied by [TN Update Controller](https://github.com/cchatterton/tn-update-controller). The old independent updater has been removed. Plugin identity, feature settings and activation scope are unchanged. Install/activate/check links use local controller detection and never fetch release metadata while rendering. Legacy update guidance below or in historical notes is superseded by this controller integration.

Release order: build and validate the ZIP, publish its matching GitHub release asset, then publish verified controller catalogue metadata. Existing update.json endpoints are maintained only for older, not-yet-migrated installations, after asset verification.

## Section editor and JSON — 0.2.0

Branding Mode: Author Branded. Author Brand: Techn.
Applies the current Codex development, WordPress plugin, Branding and UX, and GitHub update standards in cchatterton/codex-standards.

Existing valid items in `tnp_colour_palette` take precedence over defaults. On first display, groups use the text before the first `-`; an unsuffixed name gets its own group. Base, Contrast, Dark and Light appear before custom groups when inferred. Existing names, custom values, utility selectors and editor slugs remain intact. The 12 original colours are only the fallback when there are no saved items. Viewing the screen does not migrate options.

Adding a section creates three fixed identifiers (`section-1` through `section-3`). Plus cards add a new unused number; removal never renumbers surviving colours. Section labels can change without changing their identifiers. Each row scrolls horizontally. Saves regenerate the same utility CSS and clear theme JSON caches.

The JSON tab accepts a version 1 document or the original flat JSON array. RGB is derived from validated six-digit hex values. An explicit JSON save replaces the palette. AI interpretation instead merges by colour identifier, retaining unreturned colours. The previous saved document is retained for recovery. Editors reject stale revisions instead of overwriting another administrator's changes.

### Persistent data

- `tnp_colour_palette`: unchanged JSON string containing a flat array of `name`, `hex`, `rgb` objects; still the authority for colour values and compatible with 0.1.x.
- `tnp_palette_sections`: ordered section labels/slugs and their member colour identifiers. Missing metadata is inferred, including colours added by legacy code.
- `tnp_allow_custom_editor_colours`: unchanged editor preference.
- `tnp_palette_previous_json`: previous portable document, accessible from JSON.
- `tnp_palette_last_ai`: latest validated AI JSON and source attachment ID.
- `_tnp_palette_ai_json`: validated response stored on the source attachment.
- `tnp_palette_write_lock` / `tnp_ai_request_lock`: short-lived operation locks removed in finally blocks; stale locks can be retried.

### Optional WordPress AI

Uses native `wp_ai_client_prompt`, `with_file`, `using_system_instruction`, `as_json_response`, `is_supported_for_text_generation` and `generate_text`. The installed connector owns provider selection and authentication. No connector is bundled, no credentials are stored, and no dependency is required for the manual editor. The image tab appears only with a supported configured provider and upload permission. JPEG, PNG and WebP attachments must be locally readable inside uploads and at most 10 MB. The native Media Library upload limit may be lower. Generation only happens on an explicit Interpret and save action.

### Tests

Run `php tests/palette-integration.php /disposable-wordpress/wp-load.php` with the plugin active. Run `php tests/ai-integration.php /disposable-wordpress/wp-load.php` with the official `ai-provider-for-openai` plugin files installed. The latter exercises the real WordPress AI Client/provider adapter with intercepted HTTP fixtures, without credentials or paid calls. Use disposable sites only.

See [VALIDATION.md](VALIDATION.md) for tested versions, UI checks and limitations.
