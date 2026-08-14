<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Content\Models\SiteContent;
use App\Modules\Content\Support\SiteContentRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateSiteContentAction
{
    public function __construct(private readonly SiteContentRegistry $registry) {}

    /**
     * @param  array<string, string>  $content
     * @param  array<string, UploadedFile>  $images
     */
    public function execute(array $content, array $images, User $actor): void
    {
        $definitions = $this->registry->definitions();
        $storedImages = [];
        $previousImages = [];

        try {
            foreach ($images as $key => $image) {
                if (($definitions[$key]['type'] ?? null) !== 'image') {
                    continue;
                }

                $storedImages[$key] = 'storage/'.$image->store('content/'.$key, 'public');
            }

            DB::transaction(function () use ($content, $storedImages, $definitions, $actor, &$previousImages): void {
                foreach ($definitions as $key => $definition) {
                    if ($definition['type'] === 'image') {
                        if (! isset($storedImages[$key])) {
                            continue;
                        }

                        $previousImages[$key] = SiteContent::query()->where('key', $key)->value('value');
                        $value = $storedImages[$key];
                    } else {
                        if (! array_key_exists($key, $content)) {
                            continue;
                        }

                        $value = trim($content[$key]);
                    }

                    SiteContent::query()->updateOrCreate(
                        ['key' => $key],
                        [
                            'type' => $definition['type'],
                            'value' => $value,
                            'updated_by' => $actor->getKey(),
                        ],
                    );
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedImages as $path) {
                Storage::disk('public')->delete(substr($path, strlen('storage/')));
            }

            throw $exception;
        }

        foreach ($previousImages as $path) {
            if (is_string($path) && str_starts_with($path, 'storage/')) {
                Storage::disk('public')->delete(substr($path, strlen('storage/')));
            }
        }

        $this->registry->clearCache();
    }
}
