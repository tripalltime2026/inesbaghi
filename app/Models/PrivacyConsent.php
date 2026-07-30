<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsent extends Model
{
    protected $fillable = [
        'user_id', 'subject_type', 'subject_id', 'consent_type', 'policy_version',
        'legal_basis', 'consent_text_hash', 'accepted_at', 'withdrawn_at',
        'ip_address', 'user_agent', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
