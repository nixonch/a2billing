#!/bin/bash
set -euo pipefail

read -r -p 'Database name: ' dbname
read -r -p 'Hostname [localhost]: ' hostname
read -r -p 'Username: ' username
read -r -s -p 'Password: ' password
echo

export MYSQL_PWD="$password"
unset password
connection=("${MYSQL_BIN:-mysql}" --host="${hostname:-localhost}" --user="$username"
            --database="$dbname" --default-character-set=utf8 --skip-force)

tables=$("${connection[@]}" --batch --skip-column-names \
    --execute='SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')
if [[ "$tables" != 0 ]]; then
    echo "Database '$dbname' is not empty; installation stopped." >&2
    exit 1
fi

"${connection[@]}" < "$(dirname -- "${BASH_SOURCE[0]}")/a2billing-install.sql"
echo 'Installation complete: A2Billing 3.0 (nixonch).'
