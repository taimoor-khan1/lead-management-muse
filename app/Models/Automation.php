<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $fillable = [
        'name',
        'trigger',
        'action',
        'config',
        'active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }
}
