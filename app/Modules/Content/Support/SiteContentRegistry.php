<?php

namespace App\Modules\Content\Support;

use App\Modules\Content\Models\SiteContent;
use Illuminate\Support\Facades\Schema;

class SiteContentRegistry
{
    /** @var array<string, string|null>|null */
    private ?array $storedValues = null;

    public function get(string $key): string
    {
        $definition = $this->definitions()[$key] ?? null;

        if ($definition === null) {
            return '';
        }

        return (string) ($this->values()[$key] ?? $definition['default'] ?? '');
    }

    public function asset(string $key): string
    {
        return asset($this->get($key));
    }

    public function clearCache(): void
    {
        $this->storedValues = null;
    }

    /**
     * @return array<string, array{label: string, description: string, fields: array<string, array<string, mixed>>}>
     */
    public function groupsForAdmin(): array
    {
        $groups = config('site-content.groups', []);

        foreach ($groups as &$group) {
            foreach ($group['fields'] as $key => &$field) {
                $field['key'] = $key;
                $field['value'] = $this->get($key);
            }
        }

        return $groups;
    }

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        $definitions = [];

        foreach (config('site-content.groups', []) as $group) {
            foreach ($group['fields'] as $key => $field) {
                $definitions[$key] = $field;
            }
        }

        return $definitions;
    }

    /** @return array<string, string|null> */
    private function values(): array
    {
        if ($this->storedValues !== null) {
            return $this->storedValues;
        }

        if (! Schema::hasTable('site_contents')) {
            return $this->storedValues = [];
        }

        return $this->storedValues = SiteContent::query()
            ->pluck('value', 'key')
            ->all();
    }
}
