#!/usr/bin/env bash
set -euo pipefail
# Simple log helpers
info(){ echo "• $*"; }
ok(){ echo "✓ $*"; }
warn(){ echo "! $*" >&2; }
err(){ echo "✗ $*" >&2; }

# Pretty colors (safe under set -u)
if command -v tput >/dev/null 2>&1 && [ -t 1 ]; then
  B="$(tput bold)"; G="$(tput setaf 2)"; Y="$(tput setaf 3)"; R="$(tput setaf 1)"; C="$(tput setaf 6)"; X="$(tput sgr0)"
else
  B=""; G=""; Y=""; R=""; C=""; X=""
fi


# ---------------- Helpers ----------------
require_prompt() { local v=""; while [ -z "$v" ]; do read -rp "$1: " v || true; done; echo "$v"; }
prompt() { read -rp "$1 [$2]: " v || true; echo "${v:-$2}"; }
yn_prompt() { read -rp "$1 ($2): " v || true; v="${v:-$2}"; case "${v,,}" in y|yes) echo y;; n|no) echo n;; *) echo "$2";; esac; }
slugify() { echo "$1" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g;s/^-+|-+$//g'; }
need_cmd() { command -v "$1" >/dev/null 2>&1 || { echo "Missing command: $1" >&2; exit 1; }; }
have_service() { printf '%s\n' "${SERVICES[@]}" | grep -qx "$1"; }

is_port_free() {
  local p="$1"
  if command -v ss >/dev/null 2>&1; then
    ! ss -ltn "( sport = :$p )" 2>/dev/null | grep -q ":$p "
  elif command -v lsof >/dev/null 2>&1; then
    ! lsof -iTCP -sTCP:LISTEN -P 2>/dev/null | grep -q ":$p "
  else
    (echo >"/dev/tcp/127.0.0.1/$p") >/dev/null 2>&1 && return 1 || return 0
  fi
}
next_free_port() {
  local p="$1"
  while ! is_port_free "$p"; do p=$((p+1)); done
  echo "$p"
}

# ---------------- Questions ----------------
echo "=== Laravel Template Setup (auto-ports + admin seeding + optional Livewire grid) ==="
APP_TITLE="$(require_prompt 'Project title')"
SLUG="$(slugify "$APP_TITLE")"
PROJECT_NAME="$(prompt 'Docker project name (-p)' "$SLUG")"

# Compute default free ports
NGINX_PORT="$(next_free_port 8080)"
PHPMYADMIN_PORT="$(next_free_port 8081)"

APP_URL_DEFAULT="http://localhost:${NGINX_PORT}"
APP_URL="$(prompt 'App URL' "$APP_URL_DEFAULT")"

# DB defaults to match compose (root/secret + DB=laravel)
DB_NAME="$(prompt 'DB name' 'laravel')"
DB_USER="$(prompt 'DB user' 'root')"
DB_PASS="$(prompt 'DB password' 'secret')"

# For readiness check only (root safest)
MYSQL_ROOT_PASSWORD="$(prompt 'MySQL ROOT password (readiness check only)' 'secret')"

RUN_ITEMS_SEEDERS="$(yn_prompt 'Also seed 50 demo Items for the grid? y/n' 'y')"
INCLUDE_SAMPLE_ITEMS="$RUN_ITEMS_SEEDERS"

# Compose service names
APP_SVC="${APP_SVC:-app}"
DB_SVC="${DB_SVC:-mysql}"

# ---------------- Preflight ----------------
need_cmd docker
if ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose v2 required (use 'docker compose')." >&2; exit 1
fi
[ -f docker-compose.yml ] || { echo "docker-compose.yml not found." >&2; exit 1; }

# ---------------- Determine host code dir ----------------
CODE_DIR="."
if [ -d "src" ] && { [ -f "src/artisan" ] || [ -d "src/app" ]; }; then
  CODE_DIR="src"
fi

# ---------------- Ensure writable env/storage/cache ----------------
for p in "$CODE_DIR/.env" ".env" "storage" "bootstrap/cache"; do
  [ -e "$p" ] || continue
  if [ ! -w "$p" ]; then
    if command -v sudo >/dev/null 2>&1; then
      sudo chown -R "$USER":"$USER" "$p" || true
      [ -d "$p" ] && sudo chmod -R 775 "$p" || sudo chmod 664 "$p" || true
    fi
  fi
done

# ---------------- Write fresh .env files ----------------
mkdir -p "$CODE_DIR"
write_env() {
cat > "$1" <<ENV
APP_NAME="${APP_TITLE}"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=${APP_URL}

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=${DB_SVC}
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
ENV
}
write_env ".env"
write_env "$CODE_DIR/.env"
echo "✓ Wrote .env and $CODE_DIR/.env"

# --- Generate APP_KEY safely ---
echo "• Generating APP_KEY on host…"
KEY="base64:$(head -c 32 /dev/urandom | base64)"
ESCAPED_KEY=$(printf '%s\n' "$KEY" | sed 's/[&]/\\&/g')
ENV_FILE="$CODE_DIR/.env"
if grep -q '^APP_KEY=' "$ENV_FILE" 2>/dev/null; then
  sed -i "s|^APP_KEY=.*|APP_KEY=${ESCAPED_KEY}|" "$ENV_FILE"
else
  echo "APP_KEY=${ESCAPED_KEY}" >> "$ENV_FILE"
fi

# ---------------- Continue with build/up, migrations, seeding, etc. ----------------


# ---------------- Prune domain migrations (keep only baseline) ----------------
MIG_DIR="$CODE_DIR/database/migrations"
if [ -d "$MIG_DIR" ]; then
  echo "• Pruning migrations in $MIG_DIR (removing domain-specific files)…"
  find "$MIG_DIR" -type f -name "*.php" \
    ! -name "0001_01_01_000000_create_users_table.php" \
    ! -name "0001_01_01_000001_create_cache_table.php" \
    ! -name "0001_01_01_000002_create_jobs_table.php" \
    -delete
else
  mkdir -p "$MIG_DIR"
fi

# ---------------- Optional: add sample Items + Livewire grid ----------------
if [ "$INCLUDE_SAMPLE_ITEMS" = "y" ]; then
  info "Adding sample Items entity and Livewire grid…"

  mkdir -p "$CODE_DIR/app/Models"
  cat > "$CODE_DIR/app/Models/Item.php" <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'status',
        'description',
        'created_by',
    ];
}
PHP

  ts="$(date +%Y_%m_%d_%H%M%S)"
  cat > "$MIG_DIR/${ts}_create_items_table.php" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('status', ['draft','active','archived'])->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
PHP

  mkdir -p "$CODE_DIR/database/factories"
  cat > "$CODE_DIR/database/factories/ItemFactory.php" <<'PHP'
<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'title'       => $this->faker->sentence(3),
            'status'      => $this->faker->randomElement(['draft','active','archived']),
            'description' => $this->faker->optional()->paragraph(),
            'created_by'  => null,
        ];
    }
}
PHP

  mkdir -p "$CODE_DIR/database/seeders"
  cat > "$CODE_DIR/database/seeders/ItemsSeeder.php" <<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        Item::factory()->count(50)->create();
    }
}
PHP

  mkdir -p "$CODE_DIR/app/Livewire/Items"
  cat > "$CODE_DIR/app/Livewire/Items/Table.php" <<'PHP'
<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingStatus(){ $this->resetPage(); }

    public function render()
    {
        $query = Item::query()
            ->when($this->search, fn($q) => $q->where('title','like',"%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status',$this->status))
            ->orderByDesc('id');

        return view('livewire.items.table', [
            'items' => $query->paginate(10),
        ]);
    }
}
PHP

  mkdir -p "$CODE_DIR/resources/views/livewire/items"
  cat > "$CODE_DIR/resources/views/livewire/items/table.blade.php" <<'BLADE'
<div class="space-y-4">
  <div class="flex gap-2">
    <input type="text" wire:model.live="search" placeholder="Search title…" class="border rounded px-3 py-2 w-full">
    <select wire:model.live="status" class="border rounded px-3 py-2">
      <option value="">All</option>
      <option value="draft">Draft</option>
      <option value="active">Active</option>
      <option value="archived">Archived</option>
    </select>
  </div>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="min-w-full">
      <thead>
      <tr class="bg-gray-50 text-left text-sm">
        <th class="px-4 py-3">ID</th>
        <th class="px-4 py-3">Title</th>
        <th class="px-4 py-3">Status</th>
        <th class="px-4 py-3">Created</th>
      </tr>
      </thead>
      <tbody>
      @foreach($items as $item)
        <tr class="border-t">
          <td class="px-4 py-3">{{ $item->id }}</td>
          <td class="px-4 py-3">{{ $item->title }}</td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 rounded text-xs
              @class([
                'bg-yellow-100 text-yellow-800' => $item->status==='draft',
                'bg-green-100 text-green-800' => $item->status==='active',
                'bg-gray-200 text-gray-700' => $item->status==='archived',
              ])">
              {{ ucfirst($item->status) }}
            </span>
          </td>
          <td class="px-4 py-3">{{ $item->created_at->format('Y-m-d H:i') }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div>{{ $items->links() }}</div>
</div>
BLADE
fi

# ---------------- Always add AdminUserSeeder ----------------
mkdir -p "$CODE_DIR/database/seeders"
cat > "$CODE_DIR/database/seeders/AdminUserSeeder.php" <<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
PHP

# ---------------- Get base services list (from base compose only) ----------------
mapfile -t SERVICES < <(docker compose -p "${PROJECT_NAME}" config --services)

# ---------------- Write docker-compose.override.yml (only for present services) ----------------
info "Writing docker-compose.override.yml with host ports…"
override_tmp="$(mktemp)"
{
  echo 'services:'
  if have_service "nginx"; then
    cat <<YML
  nginx:
    ports:
      - "${NGINX_PORT}:80"
YML
  fi
  if have_service "phpmyadmin"; then
    cat <<YML
  phpmyadmin:
    ports:
      - "${PHPMYADMIN_PORT}:80"
YML
  fi
} > "$override_tmp"
mv "$override_tmp" docker-compose.override.yml

# Normalize line endings just in case (avoid CRLF pollution)
if command -v dos2unix >/dev/null 2>&1; then
  dos2unix -q docker-compose.override.yml || true
else
  sed -i 's/\r$//' docker-compose.override.yml || true
fi

# ---------------- Validate compose before build ----------------
if ! docker compose -p "${PROJECT_NAME}" config >/dev/null; then
  err "Your compose files contain errors. See output above and fix before continuing."
  exit 1
fi

# ---------------- Build & up ----------------
info "Building images…"
docker compose -p "${PROJECT_NAME}" build

# Refresh services after override (harmless if unchanged)
mapfile -t SERVICES < <(docker compose -p "${PROJECT_NAME}" config --services)

START=()
for base in "$APP_SVC" "$DB_SVC" "nginx"; do
  have_service "$base" && START+=("$base")
done
have_service "phpmyadmin" && START+=("phpmyadmin")
have_service "certbot" && START+=("certbot")

info "Starting services (ports: nginx=${NGINX_PORT}, pma=${PHPMYADMIN_PORT})…"
docker compose -p "${PROJECT_NAME}" up -d "${START[@]}"

# ---------------- Wait for MySQL quietly ----------------
if have_service "$DB_SVC"; then
  info "Waiting for ${DB_SVC} to be ready…"
  ATTEMPTS=60
  until docker compose -p "${PROJECT_NAME}" exec -T "${DB_SVC}" sh -lc '
    export MYSQL_PWD="'"${MYSQL_ROOT_PASSWORD}"'";
    mysqladmin ping -h 127.0.0.1 -uroot --silent
  ' >/dev/null 2>&1 || [ $ATTEMPTS -le 0 ]; do
    printf "."
    sleep 2
    ATTEMPTS=$((ATTEMPTS-1))
  done
  echo
  if [ $ATTEMPTS -le 0 ]; then
    err "MySQL did not become ready in time. Check: docker compose -p ${PROJECT_NAME} logs ${DB_SVC}"
    exit 1
  fi
fi

# ---------------- Detect app dir inside container ----------------
detect_app_dir() {
  for d in "/var/www/html" "/var/www/html/src" "/app" "/app/src"; do
    if docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" sh -lc "[ -f $d/artisan ]"; then
      echo "$d"; return 0
    fi
  done
  echo "/var/www/html"
}
APP_DIR="$(detect_app_dir)"
info "Using APP_DIR inside container: $APP_DIR"

# ---------------- Align nginx docroot to APP_DIR/public (if nginx exists) ----------------
if have_service "nginx"; then
  info "Aligning nginx docroot with ${APP_DIR}/public…"
  docker compose -p "${PROJECT_NAME}" exec -T nginx sh -lc '
    set -e
    CONF="/etc/nginx/conf.d/default.conf"
    if [ -f "$CONF" ]; then
      sed -i "s#/var/www/html/public#'"${APP_DIR}"'/public#g" "$CONF" || true
      nginx -t && nginx -s reload || true
    fi
  '
fi

# ---------------- Copy .env to the actual app dir & fix perms ----------------
info "Placing .env into container at ${APP_DIR}…"
docker compose -p "${PROJECT_NAME}" cp "$CODE_DIR/.env" "${APP_SVC}:${APP_DIR}/.env"
docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" sh -lc "chown www-data:www-data ${APP_DIR}/.env || true"

# ---------------- Ensure Livewire (inside container) ----------------
info "Ensuring Livewire is installed…"
docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" bash -lc "
  set -e
  cd ${APP_DIR}
  export COMPOSER_ALLOW_SUPERUSER=1
  if ! composer show livewire/livewire --no-interaction >/dev/null 2>&1; then
    composer require livewire/livewire:^3 --no-interaction -q
  fi
"

# ---------------- Ensure Faker (inside container) ----------------
info "Ensuring Faker is installed…"
docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" bash -lc "
  set -e
  cd ${APP_DIR}
  export COMPOSER_ALLOW_SUPERUSER=1
  if ! composer show fakerphp/faker --no-interaction >/dev/null 2>&1; then
    composer require fakerphp/faker --dev --no-interaction -q
  fi
"

# ---------------- Ensure APP_KEY inside container ----------------
info "Ensuring APP_KEY is set inside container…"
docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" bash -lc "
  set -e
  cd ${APP_DIR}
  if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
  fi
"

# ---------------- Clear caches, link storage, fix perms ----------------
docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" bash -lc "
  set -e
  cd ${APP_DIR}
  php artisan config:clear
  php artisan route:clear || true
  php artisan view:clear || true
  php artisan storage:link --force || true
  mkdir -p storage bootstrap/cache
  chmod -R ug+rw storage bootstrap/cache || true
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
"

# ---------------- Migrate fresh (+ seed admin + optional items) ----------------
info "Running migrations & seeders in ${APP_SVC} (${APP_DIR})…"
SEED_CHAIN="php artisan db:seed --class=Database\\\Seeders\\\AdminUserSeeder --force"
if [ "$INCLUDE_SAMPLE_ITEMS" = "y" ] && [ "$RUN_ITEMS_SEEDERS" = "y" ]; then
  SEED_CHAIN="${SEED_CHAIN} && php artisan db:seed --class=Database\\\Seeders\\\ItemsSeeder --force"
fi

docker compose -p "${PROJECT_NAME}" exec -T "$APP_SVC" bash -lc "
  set -e
  cd ${APP_DIR}
  php artisan migrate:fresh --force && ${SEED_CHAIN}
"

cat <<MSG

${B}${G}✅ Setup complete.${X}

Project: ${APP_TITLE}
Slug:    ${SLUG}
Compose project: ${PROJECT_NAME}

Open app:        http://localhost:${NGINX_PORT}
phpMyAdmin:      http://localhost:${PHPMYADMIN_PORT}

Seeded admin user:
  Email:    admin@example.com
  Password: 12345678

Notes:
- Ensure your main Blade layout includes Livewire assets:
    @livewireStyles
    ...
    @livewireScripts

Tips:
- Containers: docker compose -p ${PROJECT_NAME} ps
- Logs (nginx): docker compose -p ${PROJECT_NAME} logs -f nginx
- Down:        docker compose -p ${PROJECT_NAME} down
MSG
