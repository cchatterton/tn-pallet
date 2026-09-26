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
