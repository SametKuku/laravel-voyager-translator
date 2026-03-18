<?php

namespace SametKuku\VoyagerTranslator\Services;

use Illuminate\Support\Facades\Http;

class GeminiTranslator
{
    private string $apiKey;
    private string $model;
    private int $bulkSize;

    private const LANGUAGE_MAP = [
        'tr' => 'Turkish',  'en' => 'English',   'es' => 'Spanish',
        'ru' => 'Russian',  'de' => 'German',     'fr' => 'French',
        'ar' => 'Arabic',   'zh' => 'Chinese',    'pt' => 'Portuguese',
        'it' => 'Italian',  'ja' => 'Japanese',   'ko' => 'Korean',
        'nl' => 'Dutch',    'pl' => 'Polish',     'uk' => 'Ukrainian',
    ];

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash', int $bulkSize = 40)
    {
        $this->apiKey   = $apiKey;
        $this->model    = $model;
        $this->bulkSize = $bulkSize;
    }

    public function translateBatch(array $texts, string $targetLocale): array
    {
        $results = [];

        foreach (array_chunk($texts, $this->bulkSize, true) as $chunk) {
            $chunkResults = $this->translateChunk(array_values($chunk), $targetLocale);
            foreach ($chunkResults as $i => $translated) {
                $results[] = $translated;
            }
        }

        return $results;
    }

    private function translateChunk(array $texts, string $targetLocale): array
    {
        if (count($texts) === 1) {
            return [$this->translateSingle($texts[0], $targetLocale)];
        }

        $language = self::LANGUAGE_MAP[$targetLocale] ?? $targetLocale;
        $numbered = implode("\n---\n", array_map(
            fn($i, $t) => "[{$i}] {$t}",
            array_keys($texts),
            $texts
        ));

        $prompt = <<<PROMPT
Translate each numbered item below to {$language}.

Rules:
- Return ONLY a valid JSON array of strings: ["translation0", "translation1", ...]
- Keep the exact same order and count as the input.
- Preserve HTML tags, XTAG0X-style tokens, URLs, and placeholders (:attr, %s, {{ var }}) exactly as-is.
- No explanations, no markdown fences, just the raw JSON array.

Items:
{$numbered}
PROMPT;

        try {
            $response = $this->callApi($prompt);
            $raw = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $cleaned = preg_replace('/^```[\w]*\n?/', '', trim($raw));
            $cleaned = preg_replace('/\n?```$/', '', $cleaned);
            $parsed = json_decode(trim($cleaned), true);

            if (is_array($parsed) && count($parsed) === count($texts)) {
                return $parsed;
            }
        } catch (\Exception) {
            // fallback below
        }

        // Fallback: one by one
        return array_map(fn($t) => $this->translateSingle($t, $targetLocale), $texts);
    }

    private function translateSingle(string $text, string $targetLocale): string
    {
        $language = self::LANGUAGE_MAP[$targetLocale] ?? $targetLocale;

        $prompt = <<<PROMPT
Translate the following text to {$language}.
Return ONLY the translated text. No explanations, no markdown.
Preserve HTML tags, XTAG0X tokens, URLs, and placeholders as-is.

{$text}
PROMPT;

        try {
            $response = $this->callApi($prompt);
            return trim($response['candidates'][0]['content']['parts'][0]['text'] ?? $text);
        } catch (\Exception) {
            return $text;
        }
    }

    private function callApi(string $prompt): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(60)->post($url, [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 8192],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->status());
        }

        return $response->json();
    }
}
