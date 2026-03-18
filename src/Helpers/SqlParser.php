<?php

namespace SametKuku\LaravelTranslator\Helpers;

class SqlParser
{
    private const SYSTEM_TABLES = [
        'users', 'roles', 'permissions', 'permission_role', 'migrations',
        'data_rows', 'data_types', 'menus', 'menu_items', 'settings',
        'failed_jobs', 'personal_access_tokens', 'password_resets',
        'password_reset_tokens', 'sessions', 'cache', 'jobs',
        'oauth_clients', 'oauth_access_tokens', 'telescope_entries',
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Parse all INSERT INTO `translations` rows.
     * Returns: [ "table:col:fk" => [ "locale" => "value", ... ], ... ]
     */
    public function parseTranslations(string $sql): array
    {
        $groups = [];

        foreach ($this->parseInsertStatements($sql) as $stmt) {
            if (strtolower($stmt['table']) !== 'translations') continue;

            $cols = $stmt['columns'];
            foreach ($stmt['rows'] as $row) {
                if (count($row) !== count($cols)) continue;

                $r = array_combine($cols, $row);
                $tableName  = $r['table_name']  ?? '';
                $columnName = $r['column_name'] ?? '';
                $foreignKey = $r['foreign_key'] ?? '';
                $locale     = $r['locale']       ?? '';
                $value      = $r['value']        ?? '';

                if (empty($tableName) || empty($locale)) continue;

                $key = "{$tableName}:{$columnName}:{$foreignKey}";
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'table_name'  => $tableName,
                        'column_name' => $columnName,
                        'foreign_key' => $foreignKey,
                        'locales'     => [],
                    ];
                }
                $groups[$key]['locales'][$locale] = $value;
            }
        }

        return $groups;
    }

    /**
     * Parse all non-system tables (to detect native language from raw content).
     * Returns: [ "table:id:col" => "value", ... ]
     */
    public function parseModelTables(string $sql): array
    {
        $data = [];

        foreach ($this->parseInsertStatements($sql) as $stmt) {
            $table = strtolower($stmt['table']);
            if ($table === 'translations' || in_array($table, self::SYSTEM_TABLES)) continue;

            $cols = $stmt['columns'];
            foreach ($stmt['rows'] as $row) {
                if (count($row) !== count($cols)) continue;

                $r  = array_combine($cols, $row);
                $id = $r['id'] ?? null;
                if (!$id) continue;

                foreach ($r as $col => $val) {
                    if ($col === 'id' || $val === null || mb_strlen((string)$val) < 5) continue;
                    $data["{$stmt['table']}:{$id}:{$col}"] = (string) $val;
                }
            }
        }

        return $data;
    }

    /**
     * Heuristically detect the dominant language in model table content.
     */
    public function detectModelLanguage(array $modelData): string
    {
        $sample = array_slice(array_values($modelData), 0, 300);
        $text   = implode(' ', array_filter($sample, fn($v) => is_string($v)));

        if (empty($text)) return 'en';

        $totalChars = mb_strlen($text);
        $detectors  = [
            'tr' => ['/[ğüşıöçĞÜŞİÖÇ]/',                   0.015],
            'ar' => ['/[\x{0600}-\x{06FF}]/u',               0.05],
            'ru' => ['/[\x{0400}-\x{04FF}]/u',               0.08],
            'zh' => ['/[\x{4E00}-\x{9FFF}]/u',               0.08],
            'ja' => ['/[\x{3040}-\x{30FF}]/u',               0.05],
            'ko' => ['/[\x{AC00}-\x{D7AF}]/u',               0.05],
            'de' => ['/[äöüßÄÖÜ]/',                          0.015],
            'fr' => ['/[àâæçéèêëîïôœùûüÿÀÂÆÇÉÈÊËÎÏÔŒÙÛÜŸ]/', 0.015],
        ];

        foreach ($detectors as $lang => [$pattern, $threshold]) {
            preg_match_all($pattern, $text, $m);
            if (count($m[0]) / max($totalChars, 1) >= $threshold) {
                return $lang;
            }
        }

        return 'en';
    }

    /**
     * Return locale frequency map sorted descending.
     * e.g. ['tr' => 1200, 'en' => 400]
     */
    public function getLocaleCounts(array $groups): array
    {
        $counts = [];
        foreach ($groups as $group) {
            foreach ($group['locales'] as $locale => $val) {
                if (!empty($val)) {
                    $counts[$locale] = ($counts[$locale] ?? 0) + 1;
                }
            }
        }
        arsort($counts);
        return $counts;
    }

    // -------------------------------------------------------------------------
    // Core parser
    // -------------------------------------------------------------------------

    /**
     * Find all INSERT INTO statements and parse them into structured form.
     */
    private function parseInsertStatements(string $sql): array
    {
        $results = [];

        preg_match_all(
            '/INSERT\s+(?:IGNORE\s+|LOW_PRIORITY\s+)?INTO\s+`?(\w+)`?\s*\(([^)]+)\)\s*VALUES\s*/i',
            $sql,
            $headers,
            PREG_OFFSET_CAPTURE
        );

        foreach ($headers[0] as $idx => $match) {
            $tableName  = $headers[1][$idx][0];
            $columnsRaw = $headers[2][$idx][0];
            $offset     = $match[1] + strlen($match[0]);
            $columns    = array_map(fn($c) => trim(trim($c), '`"\' '), explode(',', $columnsRaw));
            $rows       = $this->parseValueTuples($sql, $offset);

            $results[] = [
                'table'   => $tableName,
                'columns' => $columns,
                'rows'    => $rows,
            ];
        }

        return $results;
    }

    /**
     * Parse one or more (val, val, ...) tuples starting at $offset in $sql.
     * Returns array of arrays of values (strings or null).
     */
    private function parseValueTuples(string $sql, int $offset): array
    {
        $rows = [];
        $i    = $offset;
        $len  = strlen($sql);

        // Skip leading whitespace
        while ($i < $len && ($sql[$i] === ' ' || $sql[$i] === "\t" || $sql[$i] === "\r" || $sql[$i] === "\n")) {
            $i++;
        }

        while ($i < $len && $sql[$i] === '(') {
            $i++; // skip '('
            $values  = [];
            $current = '';
            $inStr   = false;

            while ($i < $len) {
                $c = $sql[$i];

                if ($inStr) {
                    if ($c === '\\' && $i + 1 < $len) {
                        $next     = $sql[$i + 1];
                        $current .= match ($next) {
                            'n'  => "\n",
                            'r'  => "\r",
                            't'  => "\t",
                            '\\' => '\\',
                            '\'' => "'",
                            '"'  => '"',
                            '0'  => "\0",
                            default => $next,
                        };
                        $i += 2;
                        continue;
                    }
                    if ($c === "'") {
                        // MySQL doubled-quote escape: ''
                        if ($i + 1 < $len && $sql[$i + 1] === "'") {
                            $current .= "'";
                            $i       += 2;
                            continue;
                        }
                        $inStr = false;
                    } else {
                        $current .= $c;
                    }
                } else {
                    if ($c === "'") {
                        $inStr = true;
                    } elseif ($c === ',') {
                        $values[] = $current === 'NULL' ? null : $current;
                        $current  = '';
                    } elseif ($c === ')') {
                        $values[] = $current === 'NULL' ? null : $current;
                        $i++;
                        break;
                    } else {
                        $current .= $c;
                    }
                }

                $i++;
            }

            $rows[] = $values;

            // Skip comma and whitespace between tuples
            while ($i < $len && ($sql[$i] === ',' || $sql[$i] === ' ' || $sql[$i] === "\t" || $sql[$i] === "\r" || $sql[$i] === "\n")) {
                $i++;
            }

            // Stop if next char isn't '(' — means we're past this INSERT
            if ($i >= $len || $sql[$i] !== '(') break;
        }

        return $rows;
    }
}
