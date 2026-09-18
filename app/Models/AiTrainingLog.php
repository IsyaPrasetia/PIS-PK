<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrainingLog extends Model
{
    protected $fillable = [
        'catatan',
        'draft_json',
        'final_json',
        'created_by',
        'created_at',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'draft_json' => 'array',
            'final_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
