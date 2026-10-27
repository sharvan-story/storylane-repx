# Storylane RepX for WordPress (dev notes)

## Try it in your browser

[Open the Storylane RepX preview](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/sharvan-story/storylane-repx/main/playground/blueprint.json)

This opens a temporary WordPress in your browser with the plugin installed and RepX switched on. Nothing is installed on your computer, and the site disappears when you close the tab. You land on Settings > RepX. Click the site name at the top left to see RepX on the site.

Plugin source: `storylane-repx/`. No build step, no dependencies.

## Develop
Edit files in `storylane-repx/`. Lint with `php -l storylane-repx/*.php`.

## Test (WordPress Playground, no PHP/Docker needed)
Playground CLI needs Node 20+. Install once in a scratch folder: `npm i @wp-playground/cli`.

1. Start the server (WP_DEBUG on, plugin activated, admin/password):
   `wp-playground-cli server --port=9411 --mount="$PWD/storylane-repx:/wordpress/wp-content/plugins/storylane-repx" --blueprint="$PWD/tests/blueprint.json"`
2. In another shell: `BASE=http://127.0.0.1:9411 tests/e2e.sh` (settings save, validation, front-end output, disabled state).
3. Activation and uninstall: `run-blueprint --blueprint=tests/blueprint-uninstall.json` with mounts `plugin folder:/plugin-src`, `tests:/tests`, `some dir:/out`; report in `/out/uninstall.txt`.
4. Plugin Check (static checks): `PG=<playground dir> PC=<unzipped plugin-check> tests/run-plugin-check.sh`. Runtime checks crash in Playground ("unreachable"), so run those on a real WordPress site before submitting.

## Build
`./build.sh` writes `dist/storylane-repx.zip` (plugin folder only).

## Submit
1. Log in to wordpress.org with the @storylane.io account (2FA on).
2. Replace the `Contributors:` line in `readme.txt` with the real wordpress.org username.
3. Run `./build.sh`, upload the zip at https://wordpress.org/plugins/developers/add/.
4. Confirm the slug `storylane-repx` in the review email. After approval, add assets (see `assets-todo.md`) and commit via SVN.
