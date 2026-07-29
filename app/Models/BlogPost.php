<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    public const STATUSES = [
        'draft' => 'დრაფტი',
        'published' => 'გამოქვეყნებული',
        'archived' => 'არქივი',
    ];

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'category',
        'status',
        'published_at',
        'cover_image',
        'cover_mime',
        'cover_name',
        'cover_alt',
        'sort_order',
        'updated_by',
    ];

    protected $hidden = ['cover_image'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }
}
