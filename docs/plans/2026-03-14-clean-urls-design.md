# Clean URLs (Phase 2) Design

## Goal
Replace `/podcast.php?id=123` with `/podcast/my-topic-slug` across the entire site.

## Architecture

### Database
Add `slug VARCHAR(200) NOT NULL DEFAULT '' UNIQUE` to the `podcasts` table. Backfill all existing rows with slugs derived from their titles using `slugify()`. The slug is generated once at creation time and never changes, so URLs stay stable even if a title is edited.

### Slug generation
A `slugify($title)` helper:
- Lowercase
- Replace spaces and non-alphanumeric chars with hyphens
- Collapse multiple hyphens into one
- Trim leading/trailing hyphens
- Max 200 chars

A `generateUniqueSlug($pdo, $title)` wrapper checks for collisions and appends `-2`, `-3`, etc. if needed.

Both functions live in `db.php` (already required everywhere).

### Routing
`.htaccess` rewrite rule:
```
RewriteRule ^podcast/([^/]+)/?$ /podcast.php?slug=$1 [L,QSA]
```
Added before the existing block rules.

### podcast.php
Accept `?slug=` and look up `WHERE slug = ? AND published = 1`. Keep `?id=` as a silent fallback (for any old bookmarks). Update canonical URL, all JSON-LD URLs, and breadcrumb to use slug-based URL.

### admin.php
On `add_podcast` action: call `generateUniqueSlug($pdo, $title)` and include `slug` in the INSERT.

### Internal links updated
- `partials/podcast-card.php` — link href
- `sitemap.php` — `<loc>` URLs
- `feed.php` — `<link>`, `<guid>`, `<enclosure url>`

### mailer.php
Check if `sendDoneNotification()` builds a podcast URL and update to slug-based if so.

## Migration SQL
```sql
ALTER TABLE podcasts ADD COLUMN slug VARCHAR(200) NOT NULL DEFAULT '' AFTER title;
ALTER TABLE podcasts ADD UNIQUE KEY uq_podcast_slug (slug);
-- Backfill: handled in PHP migration script, not raw SQL
```

## No redirects needed
The site has no established indexed URLs yet, so no 301 redirects from old `?id=` URLs are required. The `?id=` fallback in `podcast.php` is a convenience only.
