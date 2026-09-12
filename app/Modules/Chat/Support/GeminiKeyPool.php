<?php

namespace App\Modules\Chat\Support;

use Illuminate\Support\Facades\Cache;

class GeminiKeyPool
{
    /** @return array<int, array{index: int, key: string}> */
    public function orderedAvailableKeys(): array
    {
        $keys = config('chat.gemini.keys', []);
        $count = count($keys);
        if ($count === 0) {
            return [];
        }

        $start = Cache::lock('chat:gemini:key-cursor-lock', 5)->block(2, function () use ($count): int {
            $cursor = (int) Cache::get('chat:gemini:key-cursor', -1);
            $next = ($cursor + 1) % $count;
            Cache::forever('chat:gemini:key-cursor', $next);

            return $next;
        });

        $ordered = [];
        for ($offset = 0; $offset < $count; $offset++) {
            $index = ($start + $offset) % $count;
            if (! Cache::has($this->cooldownKey($index))) {
                $ordered[] = ['index' => $index, 'key' => $keys[$index]];
            }
        }

        return $ordered;
    }

    public function markCooldown(int $index, ?int $seconds = null): void
    {
        Cache::put(
            $this->cooldownKey($index),
            true,
            max(60, min(300, $seconds ?? (int) config('chat.gemini.cooldown_seconds', 120))),
        );
    }

    /** @return array<int, array{label: string, status: string}> */
    public function statuses(): array
    {
        return collect(config('chat.gemini.keys', []))->keys()->map(fn (int $index): array => [
            'label' => 'Key #'.($index + 1),
            'status' => Cache::has($this->cooldownKey($index)) ? 'cooldown' : 'active',
        ])->all();
    }

    private function cooldownKey(int $index): string
    {
        return "chat:gemini:key:{$index}:cooldown";
    }
}
