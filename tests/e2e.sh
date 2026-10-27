#!/usr/bin/env bash
# End-to-end test against a running Playground server (see README.md).
# Usage: BASE=http://127.0.0.1:9411 ./e2e.sh
BASE="${BASE:-http://127.0.0.1:9411}"
J="$(mktemp)"; trap 'rm -f "$J"' EXIT
pass=0; fail=0
ok(){ echo "PASS: $1"; pass=$((pass+1)); }
no(){ echo "FAIL: $1"; fail=$((fail+1)); }

# Abort if WordPress is not reachable, so "absence" checks can't pass against an empty response.
if ! curl -s -L -m 30 -b "playground_auto_login_already_happened=1" "$BASE/" | grep -q "wp-includes\|wp-content"; then echo "ABORT: WordPress not reachable at $BASE"; exit 2; fi
# Warm-up request: the first request after boot can be slow or incomplete.
curl -s -o /dev/null "$BASE/"

curl -s -c "$J" -b "$J" -o /dev/null "$BASE/wp-login.php"
curl -s -c "$J" -b "$J" -o /dev/null -d "log=admin&pwd=password&wp-submit=Log+In&redirect_to=$BASE/wp-admin/&testcookie=1" "$BASE/wp-login.php"

# save <script> <enabled(1|"")>  -> prints the settings page after redirect
save(){
  local page nonce
  page="$(curl -s -b "$J" -c "$J" "$BASE/wp-admin/options-general.php?page=storylane-repx")"
  nonce="$(echo "$page" | grep -o 'name="_wpnonce" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')"
  local args=(--data-urlencode "option_page=storylane_repx" --data-urlencode "action=update" --data-urlencode "_wpnonce=$nonce"
    --data-urlencode "_wp_http_referer=/wp-admin/options-general.php?page=storylane-repx"
    --data-urlencode "storylane_repx_settings[script]=$1")
  [ -n "$2" ] && args+=(--data-urlencode "storylane_repx_settings[enabled]=1")
  curl -s -L -b "$J" -c "$J" "${args[@]}" "$BASE/wp-admin/options.php"
}
tag(){ echo "<script src=\"https://js.storylane.io/js/v1/repx.js\" data-id=\"$1\" async></script>"; }
field(){ curl -s -b "$J" "$BASE/wp-admin/options-general.php?page=storylane-repx" | sed -n '/<textarea id="storylane-repx-script"/,/<\/textarea>/p' | tr -d '\n'; }
ANON="playground_auto_login_already_happened=1"
front(){ curl -s -L -b "$ANON" "$BASE/?nocache=$RANDOM"; }

echo "== Test 2: saving"
# Reset to a different ID first so the checks cannot pass on a value left by a previous run.
save "$(tag resetid000)" 1 >/dev/null; f="$(field)"; [[ "$f" == *resetid000* ]] && ok "reset to sentinel ID" || no "reset to sentinel ID: [$f]"
out="$(save '<script src="https://js.storylane.io/js/v1/repx.js" data-id="MBTX79fn0yum" async></script>' 1)"
echo "$out" | grep -qi "Settings saved" && ok "success notice shown" || no "success notice"
f="$(field)"; [[ "$f" == *'data-id=&quot;mbtx79fn0yum&quot;'* || "$f" == *'data-id="mbtx79fn0yum"'* ]] && ok "script saved, box shows the full script with the ID" || no "script save: [$f]"
[[ "$f" == *js.storylane.io/js/v1/repx.js* ]] && ok "box shows a script, not a bare ID" || no "box content"
out="$(save "<script data-id='abcdef123' src='https://js.storylane.io/js/v1/repx.js'></script>" 1)"; f="$(field)"; [[ "$f" == *abcdef123* ]] && ok "single-quoted script" || no "single quotes"
save "$(tag mbtx79fn0yum)" 1 >/dev/null
for bad in 'mbtx79fn0yum' 'not a valid script!!' '<script src="https://example.com/x.js" data-id="mbtx79fn0yum"></script>' "$(tag ab)"; do
  out="$(save "$bad" 1)"
  echo "$out" | grep -q "That is not a RepX script" && ok "rejected: ${bad:0:40}" || no "not rejected: $bad"
done
[ "$(echo "$out" | grep -c 'That is not a RepX script')" -eq 1 ] && ok "error shown once" || no "error duplicated"
f="$(field)"; [[ "$f" == *mbtx79fn0yum* ]] && ok "old value kept after bad input" || no "old value kept"
curl -s -b "$J" "$BASE/wp-admin/options-general.php?page=storylane-repx" | grep -qiE 'name="[^"]*\[location\]"|Only the ID is saved' && no "location option or ID note still present" || ok "no location option, no ID note"
p="$(curl -s -b "$J" "$BASE/wp-admin/options-general.php?page=storylane-repx")"
[ "$(echo "$p" | grep -n 'storylane-repx-enabled' | head -1 | cut -d: -f1)" -lt "$(echo "$p" | grep -n 'storylane-repx-script' | head -1 | cut -d: -f1)" ] && ok "Show RepX toggle is above the script box" || no "toggle order"

echo "== Test 3: front end"
save "$(tag mbtx79fn0yum)" 1 >/dev/null
h="$(front)"
echo "$h" | grep -o '<script[^>]*repx.js[^>]*>'
[ "$(echo "$h" | grep -c 'js.storylane.io/js/v1/repx.js')" -eq 1 ] && ok "exactly one script tag" || no "count"
echo "$h" | grep -q 'src="https://js.storylane.io/js/v1/repx.js"' && ok "src exact, no ?ver" || no "src"
echo "$h" | grep -o '<script[^>]*repx.js[^>]*>' | grep -q 'data-id="mbtx79fn0yum"' && ok "data-id" || no "data-id"
echo "$h" | grep -o '<script[^>]*repx.js[^>]*>' | grep -q 'data-source="wordpress"' && ok "data-source" || no "data-source"
echo "$h" | grep -o '<script[^>]*repx.js[^>]*>' | grep -q ' async' && ok "async" || no "async"
pos=$(echo "$h" | grep -n 'repx.js' | head -1 | cut -d: -f1); hend=$(echo "$h" | grep -n '</head>' | head -1 | cut -d: -f1)
[ "$pos" -gt "$hend" ] && ok "in footer" || no "footer placement"
curl -s -b "$ANON" "$BASE/?feed=rss2" | grep -q repx.js && no "script in feed" || ok "not in feed"
curl -s -b "$ANON" "$BASE/wp-json/" | grep -q repx.js && no "script in REST" || ok "not in REST"
curl -s -b "$J" "$BASE/wp-admin/index.php" | grep -q repx.js && no "script in admin" || ok "not in admin"
save "$(tag mbtx79fn0yum)" "" >/dev/null; h="$(front)"
echo "$h" | grep -q repx.js && no "printed while disabled" || ok "nothing when disabled"

echo "== Test 1: PHP notices (WP_DEBUG_DISPLAY on) in settings page and front end"
save "$(tag mbtx79fn0yum)" 1 >/dev/null
for u in "$BASE/wp-admin/options-general.php?page=storylane-repx" "$BASE/wp-admin/plugins.php"; do
  curl -s -b "$J" "$u" | grep -E -i "(<b>)?(Notice|Warning|Deprecated|Fatal error)(</b>)?:" && no "notice at $u" || ok "no PHP notices at $u"
done
front | grep -E -i "(<b>)?(Notice|Warning|Deprecated|Fatal error)(</b>)?:" && no "notice on front end" || ok "no PHP notices on front end"
curl -s -b "$J" "$BASE/wp-admin/plugins.php" | grep -o '<a href="[^"]*options-general.php?page=storylane-repx">Settings</a>' | head -1 | grep -q Settings && ok "Settings link on Plugins screen" || no "Settings link"
echo "passed=$pass failed=$fail"; [ "$fail" -eq 0 ]
