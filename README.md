# CS3B : Bulletin — 2026–2027

A PHP + MySQL class bulletin for BSCS 3B.

## Features
- Public announcements
- Image and video uploads
- YouTube/Vimeo hyperlinked video embeds
- Visitor upvotes without login
- Officer/admin login
- Officer directory with profile photos and social links
- Signed-in password changes and Admin password recovery
- Announcement CRUD
- Draft/published status
- Contact form for concerns, issues, reports, suggestions
- Private report management
- CSRF protection and prepared SQL statements

## Local setup
1. Create a MySQL database named `cs3b_bulletin`.
2. Select that database, then import `database.sql`. The import creates all six starter accounts.
3. Edit `config.php` or set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`.
4. Make `uploads/` and its `images/`, `videos/`, and `reports/` subdirectories writable by PHP. Profile photos are stored in `uploads/images/`.
5. Open `./` and sign in at `/login`.

Database settings can also be provided through `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables. In the supplied dev container, start the preview with `DB_USER=codespace php -d extension=/usr/lib/php/20230831/pdo_mysql.so -S 0.0.0.0:8000` after starting MariaDB.

## InfinityFree
1. Create a MySQL database and user in the InfinityFree control panel; note the exact hostname, database name, username, and password shown there.
2. Open phpMyAdmin for that database, select it, and import `database.sql`. Do not try to create the database in the SQL file; InfinityFree provisions it for you.
3. Set `$host`, `$db`, `$user`, and `$pass` in `config.php` to the hosting values. Do not use the local development credentials.
4. Upload the PHP files, `.htaccess`, and `assets/` to `htdocs`; upload the `uploads/` directories as well and ensure PHP can write there.
5. Visit `/login` directly. The database import is for a new, empty database. For an existing database, do not re-import the whole file. In phpMyAdmin, run this migration once before using profile and password recovery features:

```sql
ALTER TABLE users
	MODIFY role ENUM('admin','officer','moderator','president','vice_president','secretary','treasurer') NOT NULL DEFAULT 'officer',
	ADD default_password VARCHAR(255) NULL,
	ADD photo_path VARCHAR(255) NULL,
	ADD contact_email VARCHAR(190) NULL,
	ADD facebook_url VARCHAR(500) NULL,
	ADD linkedin_url VARCHAR(500) NULL;
UPDATE users SET default_password=password WHERE default_password IS NULL;
ALTER TABLE users MODIFY default_password VARCHAR(255) NOT NULL;
```

Then run the seed `INSERT` block only if those accounts do not already exist. Existing accounts' current password hashes become their initial reset values during migration.

The seeded starter roles are Admin, Moderator, President, Vice-President, Secretary, and Treasurer. Their emails and initial passwords are provided separately; only password hashes are stored in `database.sql`. Users can change their password while signed in. If someone forgets it, an Admin can restore that user's initial password from the Dashboard. This recovery is intentionally Admin-only; do not expose a public reset-to-default form. To rotate a password manually, generate a PHP hash locally with `php -r 'echo password_hash("YOUR_NEW_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'`, then update that user's `password` value in phpMyAdmin with the hash. Keep passwords private; never commit plaintext passwords to a public repository.

## Upload limits
The starter uses 5 MB for images/reports and 30 MB for videos. Your hosting provider's PHP upload limits may be lower; adjust them only when the host allows it.

## URL style
The site is configured with `.htaccess` so public URLs do not show `.php`, e.g. `/login`, `/dashboard`, `/report-a-problem`, `/officers`, `/profile`, and `/announcement-form`. `/contact` remains as a legacy alias for the problem form.
