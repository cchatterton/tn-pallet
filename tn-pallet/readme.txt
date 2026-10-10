=== TN Pallet ===
Contributors:
Tags: techn
Requires at least: 7.0
Tested up to: 7.1.3
Stable tag: 0.2.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage a named colour palette and generated utility CSS from WordPress admin.

== Description ==

Manage a named colour palette and generated utility CSS from WordPress admin.

== Installation ==

1. Upload tn-pallet.zip through Plugins > Add Plugin > Upload Plugin.
2. Activate the plugin in the same site or network scope as your existing installation.
3. Open Appearance > Palette. Existing colours are grouped without renaming; a new installation starts with the original defaults.
4. Use JSON to copy, download or paste a palette between sites.
5. Optionally configure an image-capable provider under Settings > Connectors to enable From image.

== Changelog ==

= 0.2.1 =
* Align the header with Content Planner and Persona26: Pallet title without the TN prefix, purpose-led eyebrow, installed version at the top right, and capability chips.
* Remove the standalone author label from the hero; retain plugin author metadata. Show the AI chip only when image interpretation is available.
* Keep the header responsive with wrapping chips and preserve all palette data and workflows.


= 0.2.0 =
* Split native Help into sections, CSS examples, class patterns, colour reference, JSON transfer and editor topics, with image help when available.
* Group existing saved colours using the prefix before the first hyphen without changing identifiers or values. Load the original 12 defaults only when no saved items exist.
* Add repeatable sections with three automatically named colour cards, horizontal scrolling, plus cards and section removal.
* Add a permanent JSON editor with copy, download, legacy-array import, validation, previous-save recovery and concurrent-edit protection.
* Integrate configured WordPress AI connectors for Media Library mockup interpretation and automatic validated JSON/palette saves; retain colours absent from AI responses.
* Apply Techn Author Branded UI, scoped rem styles, accessible tabs and feedback, nonce/capability checks and atomic CSS publication.


= 0.1.14 =
* Lower the PHP requirement to 7.4 to match WordPress 7.0; update the controller installation compatibility check.

= 0.1.13 =
* Replace the independent updater with TN Update Controller integration.
* Standardise author and plugin-row links; preserve feature settings and plugin identity.
* Require WordPress 7.0+ and PHP 8.5+.


== Managed updates ==

Install and activate TN Update Controller to discover and install updates. The plugin row offers Install Techn Update Controller, Activate Techn Update Controller, or Check for updates according to local state and permissions. Feature operation does not require the controller. No release lookup happens while rendering this plugin's row. On multisite the controller must be network active. This plugin release requires WordPress 7.0 and PHP 7.4 or later.

== Controller installation service ==

Only an explicit authorised Install Techn Update Controller action downloads the official controller ZIP from GitHub. No plugin settings or site inventory are submitted; GitHub receives the server IP address and normal request metadata. Routine update discovery is delegated to the installed controller. Repository links open GitHub when selected.
Terms: https://docs.github.com/en/site-policy/github-terms/github-terms-of-service
Privacy: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement

== Frequently Asked Questions ==

= What happens to my existing colours? =
Saved palette items are used first. Their names and values stay unchanged and groups are derived from the text before the first hyphen. A name without a hyphen gets its own group. Only an empty palette uses the original Base, Contrast, Dark and Light defaults. Saving preserves the legacy flat JSON option and CSS class format.

= Does an import merge or replace? =
Save JSON replaces the palette after confirmation and validation. The previous saved document remains available on the JSON tab. AI interpretation updates matching colours and adds new ones, retaining existing colours not returned by AI.

= Where is the AI JSON? =
The JSON tab always contains the complete current document. It also shows the last validated AI response after interpretation, even if the connector is later disconnected. The response is saved as attachment metadata on the source media item.

== External services ==

Optional AI interpretation uses the provider configured in WordPress Settings > Connectors. TN Pallet does not store API keys or connect directly to a provider. The WordPress AI Client may retrieve provider model availability when checking support. Only selecting Interpret and save submits the selected image, extraction instructions and saved palette to that configured provider. No other media or page content is submitted. Provider charges and data retention policies apply. Images remain in Media Library until an administrator removes them. Responses must pass local JSON validation before changing the palette.

Provider terms depend on the connector you configure. Common native connectors:
OpenAI terms: https://openai.com/policies/services-agreement/
OpenAI privacy: https://openai.com/policies/privacy-policy/
Google Gemini terms: https://ai.google.dev/gemini-api/terms
Google privacy: https://policies.google.com/privacy
Anthropic terms: https://www.anthropic.com/legal/commercial-terms
Anthropic privacy: https://www.anthropic.com/legal/privacy
For any other connector, consult its service disclosures before enabling it.

GitHub is used for controller installation as described above; routine update discovery is delegated to TN Update Controller.
