<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UploadMediaAssetsAction
{
    /** @param array<int, UploadedFile> $files */
    public function execute(User $user, array $files, ?string $altText): Collection
    {
        return DB::transaction(fn (): Collection => collect($files)->map(fn (UploadedFile $file): MediaAsset => MediaAsset::query()->create([
            'uploaded_by' => $user->getKey(),
            'path' => $file->store('media', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'alt_text' => $altText,
        ])));
    }
}
