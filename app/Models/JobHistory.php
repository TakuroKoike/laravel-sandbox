<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_uuid',
        'status',
        'job_db_id',
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
        'job_created_at',
        'details',
    ];

    /**
     * Get the payload as an array.
     */
    public function getPayloadArrayAttribute()
    {
        return json_decode($this->payload, true);
    }
}
