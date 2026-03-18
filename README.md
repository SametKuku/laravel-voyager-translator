# Laravel Voyager Translator

Laravel Artisan command to auto-translate [Laravel Voyager](https://voyager.devdojo.com/) CMS content using **Gemini AI** or **Google Translate**. Supports 14 languages, HTML-safe translation, and slug handling.

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Laravel Voyager (with `translations` table)

## Installation

```bash
composer require sametkuku/laravel-voyager-translator
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=voyager-translator-config
```

## Configuration

Add to your `.env`:

```env
# Engine: gemini or gtx (default: gtx)
VOYAGER_TRANSLATOR_ENGINE=gemini

# Required if engine=gemini
GEMINI_API_KEY=your_gemini_api_key

# Source locale (the language your content is written in)
VOYAGER_TRANSLATOR_SOURCE=tr

# Target locales (comma-separated)
VOYAGER_TRANSLATOR_TARGETS=en,es,ru,ar
```

## Usage

```bash
# Basic — uses .env settings
php artisan voyager:translate

# Specify source and targets inline
php artisan voyager:translate --from=tr --to=en,es,ru,ar

# Use Gemini AI
php artisan voyager:translate --engine=gemini --from=tr --to=en,es,ru

# Only translate missing/empty entries
php artisan voyager:translate --only-empty

# Preview without saving
php artisan voyager:translate --dry-run
```

## Supported Languages

| Code | Language   |
|------|------------|
| tr   | Turkish    |
| en   | English    |
| es   | Spanish    |
| ru   | Russian    |
| de   | German     |
| fr   | French     |
| ar   | Arabic     |
| zh   | Chinese    |
| pt   | Portuguese |
| it   | Italian    |
| ja   | Japanese   |
| ko   | Korean     |
| nl   | Dutch      |
| pl   | Polish     |
| uk   | Ukrainian  |

## How It Works

1. Reads all rows from the `translations` table where `locale = source_locale`
2. For each target language, sends content to the chosen engine in batches
3. HTML tags and special placeholders are protected during translation
4. Slug columns are automatically transliterated to URL-safe format
5. Results are saved back to the `translations` table via `updateOrInsert`

## Engines

### Google Translate (GTX) — Free, no key needed
Uses the unofficial GTX endpoint. No rate limits in small batches. Best for development or small datasets.

### Gemini AI — Fast & accurate
Uses `gemini-2.5-flash` by default. Sends up to 40 items per request (bulk mode) for maximum speed. Requires a free API key from [Google AI Studio](https://aistudio.google.com/app/apikey).

## License

MIT
