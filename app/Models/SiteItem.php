<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteItem extends Model
{
    public const TYPES = [
        'group',
        'team',
        'faq',
        'gallery',
        'club_post',
        'club_event',
        'club_poll',
        'club_topic',
    ];

    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'body',
        'badge',
        'color',
        'meta',
        'image',
        'image_mime',
        'image_name',
        'image_alt',
        'sort_order',
        'is_active',
        'updated_by',
    ];

    protected $hidden = ['image'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
