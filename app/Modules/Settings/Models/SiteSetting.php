<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'is_secret'])]
class SiteSetting extends Model
{
    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }
}
