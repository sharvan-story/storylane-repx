#!/usr/bin/env bash
# Builds dist/storylane-repx.zip containing only the plugin folder.
set -euo pipefail
cd "$(dirname "$0")"
rm -f dist/storylane-repx.zip
mkdir -p dist
zip -r -X dist/storylane-repx.zip storylane-repx -x '*.DS_Store' -x '*/.git*' -x '*/node_modules/*'
echo "Built: $(pwd)/dist/storylane-repx.zip"
unzip -l dist/storylane-repx.zip
