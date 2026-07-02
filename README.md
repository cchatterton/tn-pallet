# TN Pallet

TN Pallet is a lightweight WordPress plugin for managing a named colour palette from Appearance > Palette.

The plugin stores the complete palette as a JSON string in the `alphasys_colour_palette` option, generates `palette.css` under WordPress uploads, and enqueues that generated CSS on the front end, in admin, and in the block editor.

## GitHub Update Metadata

- GitHub owner: `cchatterton`
- GitHub repository: `tn-pallet`
- Plugin slug: `tn-pallet`
- Main plugin file: `tn-pallet/tn-pallet.php`
- Release ZIP asset name: `tn-pallet.zip`
- Author: `Techn`
- Author URL: `https://techn.com.au`
- Plugin URI: `https://github.com/cchatterton/tn-pallet/releases/latest`
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
- Create a GitHub release tag such as `v0.1.0`.
- Attach `tn-pallet.zip` to the release.
