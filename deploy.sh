#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

HOST="${DEPLOY_HOST:-you@your-vps}"
DEST="/srv/www/homeedresource/"

python3 build.py

rsync -avz --delete --checksum \
  --exclude '.git/' \
  --exclude 'design_handoff_home_ed_resource/' \
  --exclude 'build.py' \
  --exclude 'resources.json' \
  --exclude 'shot.py' \
  --exclude 'deploy.sh' \
  --exclude 'README.md' \
  --exclude '.build-stamp' \
  --exclude 'index.html.hand-edited' \
  --exclude '*.pyc' \
  --exclude '__pycache__/' \
  --exclude '.DS_Store' \
  ./ "$HOST:$DEST"

echo "deployed to $HOST:$DEST"
