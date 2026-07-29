<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionApplication extends Model
{
    public const STATUSES = [
        'new' => 'ახალი',
        'contacted' => 'დაკავშირებული',
        'tour_scheduled' => 'ტური დაგეგმილია',
        'documents' => 'დოკუმენტები',
        'approved' => 'დამტკიცებული',
        'enrolled' => 'ჩარიცხული',
        'rejected' => 'უარყოფილი',
        'archived' => 'არქივი',
    ];

    protected $fillable = [
        'guardian_user_id', 'assigned_to_user_id', 'parent_name', 'phone', 'child_name',
        'birth_year', 'preferred_group', 'academic_year', 'wants_tour', 'preferred_tour_date',
        'follow_up_at', 'tour_scheduled_at', 'comment', 'status', 'status_updated_at',
        'source', 'converted_child_id', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'wants_tour' => 'boolean',
            'preferred_tour_date' => 'date',
            'follow_up_at' => 'datetime',
            'tour_scheduled_at' => 'datetime',
            'status_updated_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder->where('parent_name', 'like', "%{$term}%")
                ->orWhere('child_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function convertedChild(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'converted_child_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AdmissionNote::class)->latest();
    }
}
