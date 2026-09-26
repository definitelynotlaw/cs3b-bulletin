# CS3B : Bulletin — 2026–2027

A PHP + MySQL class bulletin for BSCS 3B.

## Features
- Public announcements
- Image and video uploads
- YouTube/Vimeo hyperlinked video embeds
- Visitor upvotes without login
- Officer/admin login
- Announcement CRUD
- Draft/published status
- Contact form for concerns, issues, reports, suggestions
- Private report management
- CSRF protection and prepared SQL statements

## Local setup
1. Create a MySQL database.
2. Import `database.sql`.
3. Edit `config.php`.
4. Create the first admin using a PHP `password_hash()` result.
5. Make `uploads/` writable by PHP.
6. Open `./`.

## InfinityFree
Upload the PHP files to your hosting directory, create/import the MySQL database using the hosting control panel, and put the host/database/user/password values into `config.php`. Do not upload an existing local database password or real credentials into public repositories.

## Upload limits
The starter uses 5 MB for images/reports and 30 MB for videos. Your hosting provider's PHP upload limits may be lower; adjust them only when the host allows it.

## URL style
The site is configured with `.htaccess` so public URLs do not show `.php`, e.g. `/login`, `/dashboard`, `/contact`, and `/announcement-form`.
