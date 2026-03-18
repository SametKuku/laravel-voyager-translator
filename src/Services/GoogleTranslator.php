<?php

namespace SametKuku\VoyagerTranslator\Services;

use Illuminate\Support\Facades\Http;
use SametKuku\VoyagerTranslator\Helpers\HtmlProtector;

class GoogleTranslator
{
    private const GTX_URL = 'https://translate.googleapis.com/translate_a/single';

    public function translateBatch(array $texts, string $targetLocale): array
    {
        $results = [];
        $protector = new HtmlProtector();

        foreach ($texts as $text) {
            if (empty(trim($text))) {
                $results[] = $text;
                continue;
            }

            $masked = $protector->protect($text);

            try {
                $response = Http::timeout(15)->get(self::GTX_URL, [
                    'client' => 'gtx',
                    'sl'     => 'auto',
                    'tl'     => $targetLocale,
                    'dt'     => 't',
                    'q'      => $masked,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $translated = collect($data[0] ?? [])->pluck(0)->implode('');
                    $results[] = $protector->restore($translated);
                } else {
                    $results[] = $text;
                }
            } catch (\Exception) {
                $results[] = $text;
            }

            usleep(300000); // 300ms between requests
        }

        return $results;
    }
}
