<?php

namespace App\Modules\Appointments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'appointment_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
