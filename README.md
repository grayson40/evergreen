# Evergreen

Upload a yard photo, add your location and goals, get a **written planting plan** (zones, plant list, care notes) from Claude. Optional **concept render** via Gemini when `GEMINI_API_KEY` is set.

**Stack:** vanilla PHP 8+, SQLite, Tailwind (CDN). No framework.

## Requirements

- PHP **8.0+** with extensions: **pdo_sqlite**, **curl**, **mbstring**, **fileinfo**, **gd** (GD shrinks large photos under Anthropic’s ~5 MB vision limit)
- Writable **`uploads/`**, **`renders/`**, and **`database.sqlite`** (created on first use)

## Quick start

```bash
# Create .env in the project root (see table below)
mkdir -p uploads renders
chmod 775 uploads renders
touch database.sqlite && chmod 664 database.sqlite

# Dev server (raises upload limits — see php-uploads.ini)
php -c php-uploads.ini -S localhost:8080
```

Open [http://localhost:8080](http://localhost:8080).

## Environment

| Variable | Required | Purpose |
|----------|----------|---------|
| `ANTHROPIC_API_KEY` | **Yes** | Claude vision + consultation |
| `GEMINI_API_KEY` or `GOOGLE_API_KEY` | No | Concept image generation |
| `ANTHROPIC_MODEL` | No | Default: `claude-sonnet-4-6` |
| `EVERGREEN_API_MAX_SECONDS` | No | PHP/API budget (default 300) |
| `EVERGREEN_CONSULTATION_MAX_TOKENS` | No | Default 8192 |

Optional **`config.local.php`** returning an array merges into `config.php`.

## Production hints

Raise **nginx** `client_max_body_size` and **fastcgi_read_timeout** for long AI requests; match **PHP** `upload_max_filesize` / `post_max_size` to your max upload (default app limit 12 MB).

## License

Use and deploy as you like; keep API keys out of git (`.env` is ignored).
