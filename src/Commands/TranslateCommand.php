<?php

namespace SametKuku\VoyagerTranslator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SametKuku\VoyagerTranslator\Helpers\HtmlProtector;
use SametKuku\VoyagerTranslator\Helpers\SlugHelper;
use SametKuku\VoyagerTranslator\Services\GeminiTranslator;
use SametKuku\VoyagerTranslator\Services\GoogleTranslator;

class TranslateCommand extends Command
{
    protected $signature = 'voyager:translate
        {--from= : Source locale (auto-detected if not set)}
        {--to= : Target locales comma-separated (e.g. en,es,ru,ar)}
        {--engine= : Translation engine: gemini or gtx}
        {--only-empty : Only translate missing/empty translations}
        {--dry-run : Preview without saving to database}';

    protected $description = 'Auto-translate Laravel Voyager content using Gemini AI or Google Translate';

    private const HTML_TAG_PATTERN = '/<[a-zA-Z][^>]*>|<\/[a-zA-Z]+>/';

    public function handle(): int
    {
        $engine      = $this->option('engine') ?? config('voyager-translator.engine', 'gtx');
        $sourceLang  = $this->option('from')   ?? config('voyager-translator.source_locale', 'tr');
        $targetInput = $this->option('to')     ?? config('voyager-translator.target_locales', 'en,es,ru,ar');
        $targetLangs = array_filter(array_map('trim', explode(',', $targetInput)));
        $onlyEmpty   = $this->option('only-empty');
        $dryRun      = $this->option('dry-run');

        $this->info("🚀 Voyager Translator");
        $this->line("   Engine  : <fg=cyan>{$engine}</>");
        $this->line("   Source  : <fg=yellow>{$sourceLang}</>");
        $this->line("   Targets : <fg=green>" . implode(', ', $targetLangs) . "</>");
        $dryRun && $this->warn("   ⚠ DRY RUN — nothing will be saved.");
        $this->newLine();

        // Build translator
        $translator = $this->buildTranslator($engine);
        if (!$translator) return self::FAILURE;

        // Load all translation groups from DB
        $groups = $this->loadGroups($sourceLang);
        $this->info("Found <fg=cyan>" . count($groups) . "</> translation groups with source content.");

        if (empty($groups)) {
            $this->warn("No translatable content found for source locale '{$sourceLang}'.");
            $this->line("Tip: Make sure your content is stored in the <translations> table with locale='{$sourceLang}'");
            $this->line("     or that model tables contain content in that language.");
            return self::SUCCESS;
        }

        $batchSize = (int) config('voyager-translator.batch_size', 40);

        foreach ($targetLangs as $locale) {
            $this->newLine();
            $this->info("Translating → <fg=green>{$locale}</> ...");

            $toTranslate = $onlyEmpty
                ? array_filter($groups, fn($g) => empty($g['translations'][$locale] ?? ''))
                : $groups;

            $toTranslate = array_values($toTranslate);
            $total = count($toTranslate);

            if ($total === 0) {
                $this->line("  All {$locale} translations already exist. Skipping.");
                continue;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($toTranslate, $i, $batchSize);

                $isHtml = collect($batch)->contains(
                    fn($g) => preg_match(self::HTML_TAG_PATTERN, $g['source_value'])
                );

                $sourceTexts = array_column($batch, 'source_value');

                try {
                    if ($isHtml) {
                        $translated = [];
                        foreach ($sourceTexts as $text) {
                            $protector = new HtmlProtector();
                            $masked = $protector->protect($text);
                            $result = $translator->translateBatch([$masked], $locale)[0];
                            $translated[] = $protector->restore($result);
                        }
                    } else {
                        $translated = $translator->translateBatch($sourceTexts, $locale);
                    }

                    foreach ($batch as $idx => $group) {
                        $value = $translated[$idx] ?? $group['source_value'];

                        if ($group['column_name'] === 'slug') {
                            $value = SlugHelper::slugify($value, $group['source_value']);
                        }

                        if (empty($value)) continue;

                        if (!$dryRun) {
                            DB::table('translations')->updateOrInsert(
                                [
                                    'table_name'  => $group['table_name'],
                                    'column_name' => $group['column_name'],
                                    'foreign_key' => $group['foreign_key'],
                                    'locale'      => $locale,
                                ],
                                [
                                    'value'      => $value,
                                    'updated_at' => now(),
                                    'created_at' => now(),
                                ]
                            );
                        }

                        $bar->advance();
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("  Batch error: " . $e->getMessage() . " — retrying in 5s...");
                    sleep(5);
                    $i -= $batchSize; // retry
                }
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✓ {$locale} done.");
        }

        $this->newLine();
        $this->info($dryRun ? "✓ Dry run complete — no data was written." : "✓ All translations saved to database.");

        return self::SUCCESS;
    }

    private function loadGroups(string $sourceLang): array
    {
        $rows = DB::table('translations')
            ->where('locale', $sourceLang)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $key = "{$row->table_name}:{$row->column_name}:{$row->foreign_key}";
            $existing = DB::table('translations')
                ->where('table_name', $row->table_name)
                ->where('column_name', $row->column_name)
                ->where('foreign_key', $row->foreign_key)
                ->whereNotIn('locale', [$sourceLang])
                ->pluck('value', 'locale')
                ->toArray();

            $groups[$key] = [
                'table_name'   => $row->table_name,
                'column_name'  => $row->column_name,
                'foreign_key'  => $row->foreign_key,
                'source_value' => $row->value,
                'translations' => $existing,
            ];
        }

        return $groups;
    }

    private function buildTranslator(string $engine): GeminiTranslator|GoogleTranslator|null
    {
        if ($engine === 'gemini') {
            $key = config('voyager-translator.gemini_api_key');
            if (empty($key)) {
                $this->error("GEMINI_API_KEY is not set. Add it to your .env file.");
                return null;
            }
            $model = config('voyager-translator.gemini_model', 'gemini-2.5-flash');
            $bulk  = (int) config('voyager-translator.batch_size', 40);
            return new GeminiTranslator($key, $model, $bulk);
        }

        return new GoogleTranslator();
    }
}
