<?php

$geminiKeySource = implode(',', [
    (string) env('GEMINI_API_KEY', ''),
    (string) env('GEMINI_API_KEYS', ''),
]);

$geminiKeys = array_values(array_unique(array_filter(array_map(
    static fn (string $key): string => trim($key),
    explode(',', $geminiKeySource),
))));

return [
    'gemini' => [
        'keys' => $geminiKeys,
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout_seconds' => (int) env('GEMINI_TIMEOUT_SECONDS', 18),
        'cooldown_seconds' => (int) env('GEMINI_KEY_COOLDOWN_SECONDS', 120),
        'history_limit' => 10,
    ],
];
