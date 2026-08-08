<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DtcFault extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'code',
        'description',
        'severity',
        'recommended_action',
        'status',
    ];

    /**
     * Vehicle associated with the DTC fault code.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
