#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
php -r '$j=json_decode(file_get_contents("composer.json"),true,512,JSON_THROW_ON_ERROR); if(($j["require"]["php"]??null)!=="^8.1") {fwrite(STDERR,"PHP target mismatch\n"); exit(1);} echo "composer.json: valid, PHP target ^8.1\n";'
find app bootstrap config database routes tests public -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/tmp/erp_php_lint.txt
if grep -R -nE '\bjson_validate\s*\(|\breadonly\s+class\b|\benum\s+[A-Za-z_]' app config database routes tests >/tmp/erp_php_newer_features.txt; then
  cat /tmp/erp_php_newer_features.txt
  echo "Found syntax/API that needs PHP > 8.1 review" >&2
  exit 1
fi
echo "PHP source lint: OK"
echo "Known PHP >8.1 feature scan: OK"
