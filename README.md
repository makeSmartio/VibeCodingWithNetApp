# Vibe-Coding with NetApp: Webinar Registration

Simple PHP + MySQL registration app for the Friday 4:00 PM ET webinar series.

## Run locally (Docker)

```
docker compose up --build
```

Open http://localhost:8080. The database schema in `db/schema.sql` loads on first start.
Optionally copy `.env.example` to `.env` to change credentials.

## Add a session

Times are entered in Eastern time and stored in UTC.

```
docker compose exec web php bin/add_session.php "2026-11-06 16:00" "Vibe-Coding Happy Hour" "https://join.example.com/abc" 100
```

All arguments are optional (defaults: next Friday 4 PM ET, default title, no link, no capacity limit).

## View registrants

```
docker compose exec db mysql -uwebinar -pwebinar_pass webinar \
  -e "SELECT s.starts_at, r.name, r.email, r.company, r.question FROM registrations r JOIN sessions s ON s.id=r.session_id ORDER BY s.starts_at, r.created_at"
```

CSV export:

```
docker compose exec db mysql -uwebinar -pwebinar_pass webinar --batch \
  -e "SELECT * FROM registrations" | tr '\t' ','  > registrants.csv
```

## Without Docker

Create a MySQL database, run `db/schema.sql`, set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`,
and serve `public/` (e.g. `php -S localhost:8080 -t public`).
