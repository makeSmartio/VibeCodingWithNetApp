#!/usr/bin/env bash
# Run the app with Apple's `container` CLI (no Compose equivalent exists).
# Usage: scripts/apple-container.sh up | down | add-session [args...]
set -euo pipefail
cd "$(dirname "$0")/.."

# Load settings from .env (same file docker-compose uses).
if [[ -f .env ]]; then
  set -a; source .env; set +a
fi
DB_NAME="${DB_NAME:-webinar}"
DB_USER="${DB_USER:-webinar}"
for v in DB_PASS DB_ROOT_PASS; do
  if [[ -z "${!v:-}" || "${!v}" == "change-me" ]]; then
    echo "Set $v in .env (see .env.example)." >&2; exit 1
  fi
done

db_ip() {
  container inspect webinar-db | grep -o '"ipv4Address" : "[0-9.]*' | head -1 | grep -o '[0-9.]*$'
}

case "${1:-up}" in
  up)
    container system start >/dev/null 2>&1 || true
    container volume create webinar-data >/dev/null 2>&1 || true
    container run -d --name webinar-db \
      -e MYSQL_DATABASE="$DB_NAME" -e MYSQL_USER="$DB_USER" \
      -e MYSQL_PASSWORD="$DB_PASS" -e MYSQL_ROOT_PASSWORD="$DB_ROOT_PASS" \
      -v webinar-data:/var/lib/mysql \
      -v "$PWD/db/schema.sql:/docker-entrypoint-initdb.d/schema.sql:ro" \
      docker.io/library/mysql:8.0

    echo "Waiting for MySQL..."
    until container exec webinar-db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1 FROM registrations LIMIT 1" >/dev/null 2>&1; do sleep 2; done
    IP="$(db_ip)"; echo "MySQL at $IP"

    container build -t webinar-web .
    container run -d --name webinar-web -p 8080:80 \
      -e DB_HOST="$IP" -e DB_NAME="$DB_NAME" -e DB_USER="$DB_USER" -e DB_PASS="$DB_PASS" \
      webinar-web
    echo "Open http://localhost:8080"
    ;;
  down)
    container stop webinar-web webinar-db 2>/dev/null || true
    container rm webinar-web webinar-db 2>/dev/null || true
    ;;
  add-session)
    shift
    container exec webinar-web php bin/add_session.php "$@"
    ;;
  *) echo "Usage: $0 up|down|add-session [args]"; exit 1 ;;
esac
