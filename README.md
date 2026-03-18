# Laravel Voyager Translator

Laravel package to auto-translate [Laravel Voyager](https://voyager.devdojo.com/) CMS content using **Gemini AI** or **Google Translate**. Supports 15 languages, HTML-safe translation, slug handling, and comes with a built-in **web UI**.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Laravel Voyager (with `translations` table)

## Installation

```bash
composer require sametkuku/laravel-voyager-translator
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=voyager-translator-config
```

## Web UI

After installation, open your browser at:

```
http://your-app.test/voyager-translator
```

The UI lets you:

1. **Load from DB** — reads directly from your connected database
2. **Upload SQL** — upload a `.sql` dump file; language is auto-detected
3. **Select languages** — source auto-detected, pick your target locales
4. **Choose engine** — Google Translate (free) or Gemini AI (API key)
5. **Translate** — real-time progress bar per language
6. **Save or Export** — write to DB, download `.sql` or `.json`

> Add `'auth'` to the `middleware` config key to protect the route.

## Artisan Command

```bash
# Basic — uses .env settings
php artisan voyager:translate

# Override source and targets
php artisan voyager:translate --from=tr --to=en,es,ru,ar

# Use Gemini AI
php artisan voyager:translate --engine=gemini --from=tr --to=en,es,ru

# Only translate missing/empty rows
php artisan voyager:translate --only-empty

# Preview without saving
php artisan voyager:translate --dry-run
```

## Configuration

```env
# Engine: gemini or gtx (default: gtx)
VOYAGER_TRANSLATOR_ENGINE=gemini

# Required if engine=gemini
GEMINI_API_KEY=your_key_here

# Source locale (auto-detected in web UI)
VOYAGER_TRANSLATOR_SOURCE=tr

# Target locales for Artisan command
VOYAGER_TRANSLATOR_TARGETS=en,es,ru,ar

# Web UI route prefix (default: voyager-translator)
VOYAGER_TRANSLATOR_PREFIX=voyager-translator
```

## Supported Languages

| Code | Language   | Code | Language   |
|------|------------|------|------------|
| tr   | Turkish    | pt   | Portuguese |
| en   | English    | it   | Italian    |
| es   | Spanish    | ja   | Japanese   |
| ru   | Russian    | ko   | Korean     |
| de   | German     | nl   | Dutch      |
| fr   | French     | pl   | Polish     |
| ar   | Arabic     | uk   | Ukrainian  |
| zh   | Chinese    |      |            |

## How It Works

1. Reads rows from the `translations` table (or parses an uploaded SQL file)
2. Detects the source language automatically from content
3. Sends text to the chosen engine in batches
4. HTML tags and Blade/Laravel placeholders are protected during translation
5. Slug columns are transliterated to URL-safe format (Turkish, Arabic, Cyrillic)
6. Results saved to the `translations` table via `updateOrInsert`, or exported as SQL/JSON

## Engines

### Google Translate (GTX) — Free
Uses the unofficial GTX endpoint. No API key needed.

### Gemini AI — Fast & accurate
Uses `gemini-2.5-flash` with bulk requests (up to 40 items per call). Get a free API key at [Google AI Studio](https://aistudio.google.com/app/apikey).

## License

MIT
