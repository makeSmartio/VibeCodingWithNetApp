# Vibe-Coding with NetApp: Webinar Registration

Simple PHP + MySQL registration app for the Friday 4:00 PM ET webinar series.

## Run locally (Docker)

```
docker compose up --build
```

Open http://localhost:8080. The database schema in `db/schema.sql` loads on first start.
Optionally copy `.env.example` to `.env` to change credentials.

## Run with Apple's `container` CLI

Apple's `container` has no Compose, so a helper script starts MySQL and the web app:

```
scripts/apple-container.sh up
scripts/apple-container.sh add-session "2026-11-06 16:00" "Vibe-Coding Happy Hour" "https://join.example.com/abc"
scripts/apple-container.sh down
```

The database lives in the `webinar-data` volume. To query registrants, use
`container exec webinar-db mysql -uwebinar -pwebinar_pass webinar -e "..."`.
The web container's code is baked in at build, so re-run `down` then `up` after code changes.

## Add a session (Docker)

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

## Mailing list

`/subscribe.php` lets anyone join the mailing list at any time (email required; name and company optional).
Repeat sign-ups are ignored quietly. To list subscribers:

```
docker compose exec db mysql -uwebinar -pwebinar_pass webinar -e "SELECT email, name, company, created_at FROM subscribers"
```

If your database was created before this table existed, run `db/schema.sql` against it once (it only creates missing tables).
If the table exists but has no `unsubscribe_token` column, run `db/migrations/001_subscriber_unsubscribe_token.sql` once.

### Unsubscribe links

Each subscriber has a secret token. Put this link in every email you send:

```
https://YOUR-SITE/unsubscribe.php?t=<unsubscribe_token>
```

To get each subscriber's link:

```
docker compose exec db mysql -uwebinar -pwebinar_pass webinar \
  -e "SELECT email, CONCAT('https://YOUR-SITE/unsubscribe.php?t=', unsubscribe_token) AS link FROM subscribers"
```

Clicking the link shows a confirmation button; pressing it deletes the subscriber.

## Without Docker

Create a MySQL database, run `db/schema.sql`, set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`,
and serve `public/` (e.g. `php -S localhost:8080 -t public`).
