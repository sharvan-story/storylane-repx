#!/usr/bin/env bash
# Runs every static Plugin Check check against storylane-repx, one Playground run per check
# (a combined run is unreliable in Playground). Needs: PG=<dir containing node_modules/.bin/wp-playground-cli>
# and PC=<unzipped plugin-check plugin folder, from https://downloads.wordpress.org/plugin/plugin-check.latest-stable.zip>.
# Results: $PG/out/plugin-check-<slug>.txt
set -u
: "${PG:?set PG}"; : "${PC:?set PC}"
HERE="$(cd "$(dirname "$0")" && pwd)"; ROOT="$(dirname "$HERE")"; mkdir -p "$PG/out"
for s in plugin_header_fields plugin_readme file_type plugin_uninstall i18n_usage code_obfuscation plugin_content late_escaping safe_redirect plugin_updater plugin_review_phpcs direct_db_queries performant_wp_query_params enqueued_scripts_in_footer localhost no_unfiltered_uploads trademarks offloading_files write_file setting_sanitization prefixing direct_db minified_files direct_file_access external_admin_menu_links wp_functions_compatibility ai_provider; do
  cat > "$PG/bs-$s.json" <<JSON
{"preferredVersions":{"php":"8.3","wp":"latest"},"steps":[
{"step":"defineWpConfigConsts","consts":{"WP_DEBUG":true}},
{"step":"activatePlugin","pluginPath":"plugin-check/plugin.php"},
{"step":"activatePlugin","pluginPath":"storylane-repx/storylane-repx.php"},
{"step":"runPHP","code":"<?php \$GLOBALS['PC_SLUGS']=['$s']; \$GLOBALS['PC_SUFFIX']='-$s'; require '/tests/plugin-check-run.php';"}]}
JSON
  "$PG/node_modules/.bin/wp-playground-cli" run-blueprint --mount="$ROOT/storylane-repx:/wordpress/wp-content/plugins/storylane-repx" --mount="$PC:/wordpress/wp-content/plugins/plugin-check" --mount="$HERE:/tests" --mount="$PG/out:/out" --blueprint="$PG/bs-$s.json" >/dev/null 2>&1
  echo "$s: $(sed -n 3p "$PG/out/plugin-check-$s.txt")"
done
