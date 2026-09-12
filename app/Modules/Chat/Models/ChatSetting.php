<?php

namespace App\Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSetting extends Model
{
    protected $fillable = ['key', 'value'];
}
