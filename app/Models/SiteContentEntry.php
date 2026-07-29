<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContentEntry extends Model
{
    protected $fillable = [
        'key',
        'section',
        'label',
        'value',
        'input_type',
        'sort_order',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
