<?php

namespace App\Modules\Chat\Support;

use Illuminate\Support\Str;

trait NormalizesChatText
{
    private function normalize(string $text): string
    {
        return Str::of($text)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9#]+/', ' ')
            ->squish()
            ->toString();
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $text, array $needles): bool
    {
        $normalized = $this->normalize($text);

        return collect($needles)->contains(
            fn (string $needle): bool => str_contains($normalized, $this->normalize($needle)),
        );
    }
}
