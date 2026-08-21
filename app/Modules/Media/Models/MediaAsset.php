<?php

namespace App\Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uploaded_by', 'path', 'original_name', 'mime_type', 'size_bytes', 'alt_text'])]
class MediaAsset extends Model
{
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
