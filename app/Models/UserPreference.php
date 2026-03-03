<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'client_id',
        'sources',
        'categories',
        'authors',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'categories' => 'array',
            'authors' => 'array',
        ];
    }
}
