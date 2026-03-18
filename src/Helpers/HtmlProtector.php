<?php

namespace SametKuku\LaravelTranslator\Helpers;

class HtmlProtector
{
    private array $tags = [];

    public function protect(string $text): string
    {
        $this->tags = [];
        $pattern = '/<[^>]+>|https?:\/\/[^\s<"\']+|%[sd]|\{\{[^}]+\}\}/';

        return preg_replace_callback($pattern, function ($matches) {
            $this->tags[] = $matches[0];
            return 'XTAG' . (count($this->tags) - 1) . 'X';
        }, $text);
    }

    public function restore(string $text): string
    {
        return preg_replace_callback('/XTAG\s*([0-9]+)\s*X/', function ($matches) {
            $index = (int) $matches[1];
            return $this->tags[$index] ?? $matches[0];
        }, $text);
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
