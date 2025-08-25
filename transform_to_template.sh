#!/usr/bin/env bash
set -euo pipefail

backup_branch="template-backup-$(date +%Y%m%d-%H%M%S)"
if git rev-parse --git-dir >/dev/null 2>&1; then
  git checkout -b "$backup_branch"
fi

declare -a REMOVE_PATHS=(
  "app/Models/Alert.php"
  "app/Models/Site.php"
  "app/Models/DeviceToken.php"
  "app/Observers/AlertObserver.php"
  "app/Jobs/SendAlertPushNotification.php"
  "app/Jobs/SendAlertStatusChangedNotification.php"
  "app/Livewire/AlertManagement.php"
  "app/Livewire/SiteManagement.php"
  "app/Http/Controllers/Api/AlertController.php"
  "app/Http/Controllers/Api/DeviceTokenController.php"
  "resources/views/livewire/alert-management.blade.php"
  "resources/views/livewire/site-management.blade.php"
)
for p in "${REMOVE_PATHS[@]}"; do
  [ -e "$p" ] && git rm -rf "$p"
done

OVERLAY_ZIP="template_overlay.zip"
unzip -oq "$OVERLAY_ZIP" -d .

chmod +x docker/entrypoint.sh scripts/setup.sh

echo "Template applied. Run ./scripts/setup.sh to bring up containers."
