<?php

declare(strict_types=1);

class UrgenceService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $path = __DIR__ . '/../config/urgence.php';
            $config = file_exists($path) ? (array) require $path : [];
        }

        $this->config = $this->mergeDefaults($config);
    }

    public function calculerUrgence(array $temoins, string $typePanne, int $km): array
    {
        $score = 0;
        $details = [];

        $temoinsConfig = $this->normalizeConfigMap((array) ($this->config['temoins'] ?? []));
        $temoinsAliases = $this->normalizeAliasMap((array) ($this->config['temoins_aliases'] ?? []));
        $typeConfig = $this->normalizeConfigMap((array) ($this->config['types_panne'] ?? []));
        $maxScore = (int) ($this->config['max_score'] ?? 10);

        $normalizedTemoins = $this->normalizeList($temoins);
        foreach ($normalizedTemoins as $key) {
            $canonicalKey = $temoinsAliases[$key] ?? $key;
            if (!array_key_exists($canonicalKey, $temoinsConfig)) {
                continue;
            }
            $weight = (int) $temoinsConfig[$canonicalKey];
            if ($weight <= 0) {
                continue;
            }
            $score += $weight;
            $details[$canonicalKey] = $weight;
        }

        $normalizedType = $this->normalizeKey($typePanne);
        if ($normalizedType !== '' && array_key_exists($normalizedType, $typeConfig)) {
            $weight = (int) $typeConfig[$normalizedType];
            if ($weight > 0) {
                $score += $weight;
                $details['type_' . $normalizedType] = $weight;
            }
        }

        $kmConfig = (array) ($this->config['km'] ?? []);
        $seuilKm = (int) ($kmConfig['seuil'] ?? 0);
        $kmScore = (int) ($kmConfig['score'] ?? 0);
        if ($seuilKm > 0 && $km >= $seuilKm && $kmScore > 0) {
            $score += $kmScore;
            $details['km'] = $kmScore;
        }

        $score = min($score, $maxScore);

        return [
            'score' => $score,
            'details' => $details,
        ];
    }

    public function detecterTemoinsDepuisTexte(string $texte): array
    {
        $normalizedText = $this->normalizeKey($texte);
        if ($normalizedText === '') {
            return [];
        }

        $detected = [];
        $matchedPhrases = [];
        $candidates = [];
        $temoinsConfig = $this->normalizeConfigMap((array) ($this->config['temoins'] ?? []));
        $temoinsAliases = $this->normalizeAliasMap((array) ($this->config['temoins_aliases'] ?? []));

        foreach ($temoinsConfig as $canonicalKey => $_weight) {
            $candidates[] = ['phrase' => $canonicalKey, 'target' => $canonicalKey];
        }

        foreach ($temoinsAliases as $aliasKey => $canonicalKey) {
            $candidates[] = ['phrase' => $aliasKey, 'target' => $canonicalKey];
        }

        usort($candidates, static fn($a, $b) => strlen($b['phrase']) <=> strlen($a['phrase']));

        foreach ($candidates as $candidate) {
            $phrase = (string) $candidate['phrase'];
            if (!$this->containsNormalizedPhrase($normalizedText, $phrase)) {
                continue;
            }

            foreach ($matchedPhrases as $matchedPhrase) {
                if ($this->containsNormalizedPhrase($matchedPhrase, $phrase)) {
                    continue 2;
                }
            }

            $matchedPhrases[] = $phrase;
            $detected[(string) $candidate['target']] = true;
        }

        return array_keys($detected);
    }

    public function calculerScoreAvance(array $context): array
    {
        $score = 0;
        $details = [];

        $advanced = (array) ($this->config['advanced'] ?? []);
        $oldHours = (int) ($advanced['old_rdv_hours'] ?? 48);
        $oldScore = (int) ($advanced['old_rdv_score'] ?? 1);
        $ratioThreshold = (float) ($advanced['saturation_ratio'] ?? 1.0);
        $ratioScore = (int) ($advanced['saturation_score'] ?? 1);
        $historyMin = (int) ($advanced['history_min'] ?? 2);
        $historyScore = (int) ($advanced['history_score'] ?? 1);

        if (!empty($context['date_creation'])) {
            $createdAt = strtotime((string) $context['date_creation']);
            if ($createdAt !== false) {
                $ageHours = (int) floor((time() - $createdAt) / 3600);
                if ($ageHours >= $oldHours && $oldScore > 0) {
                    $score += $oldScore;
                    $details['anciennete'] = $oldScore;
                }
            }
        }

        if (isset($context['capacite_max'], $context['nb_actifs'])) {
            $capaciteMax = (int) $context['capacite_max'];
            $nbActifs = (int) $context['nb_actifs'];
            if ($capaciteMax > 0) {
                $ratio = $nbActifs / $capaciteMax;
                if ($ratio >= $ratioThreshold && $ratioScore > 0) {
                    $score += $ratioScore;
                    $details['surcharge_atelier'] = $ratioScore;
                }
            }
        }

        if (isset($context['historique_count'])) {
            $historyCount = (int) $context['historique_count'];
            if ($historyCount >= $historyMin && $historyScore > 0) {
                $score += $historyScore;
                $details['historique'] = $historyScore;
            }
        }

        return [
            'score' => $score,
            'details' => $details,
        ];
    }

    public function getUrgentMinScore(): int
    {
        return (int) ($this->config['urgent_min_score'] ?? 7);
    }

    public function clampScore(int $score): int
    {
        $maxScore = (int) ($this->config['max_score'] ?? 10);
        return max(0, min($score, $maxScore));
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    private function normalizeList(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $key = $this->normalizeKey((string) $value);
            if ($key !== '') {
                $normalized[$key] = true;
            }
        }

        return array_keys($normalized);
    }

    private function normalizeKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        $value = str_replace('_', ' ', $value);

        $translit = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($translit !== false) {
            $value = $translit;
        }

        $value = preg_replace('/[^a-z0-9\s\-]+/', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function normalizeConfigMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $key => $value) {
            $normalizedKey = $this->normalizeKey((string) $key);
            if ($normalizedKey === '') {
                continue;
            }
            $weight = (int) $value;
            if (!isset($normalized[$normalizedKey]) || $weight > $normalized[$normalizedKey]) {
                $normalized[$normalizedKey] = $weight;
            }
        }

        return $normalized;
    }

    private function normalizeAliasMap(array $map): array
    {
        $normalized = [];
        foreach ($map as $alias => $target) {
            $aliasKey = $this->normalizeKey((string) $alias);
            $targetKey = $this->normalizeKey((string) $target);
            if ($aliasKey === '' || $targetKey === '') {
                continue;
            }
            $normalized[$aliasKey] = $targetKey;
        }

        return $normalized;
    }

    private function containsNormalizedPhrase(string $text, string $phrase): bool
    {
        if ($phrase === '') {
            return false;
        }

        $pattern = '/(?<![a-z0-9])' . preg_quote($phrase, '/') . '(?![a-z0-9])/';
        return (bool) preg_match($pattern, $text);
    }

    private function mergeDefaults(array $config): array
    {
        $defaults = [
            'temoins' => [],
            'temoins_aliases' => [],
            'types_panne' => [],
            'km' => ['seuil' => 0, 'score' => 0],
            'advanced' => [
                'old_rdv_hours' => 48,
                'old_rdv_score' => 1,
                'saturation_ratio' => 1.0,
                'saturation_score' => 1,
                'history_min' => 2,
                'history_score' => 1,
            ],
            'urgent_min_score' => 7,
            'max_score' => 10,
            'broadcast' => [
                'driver' => 'sse',
                'sse_log' => __DIR__ . '/../storage/urgency-events.log',
            ],
        ];

        return array_replace_recursive($defaults, $config);
    }
}
