<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequest extends Model
{
    public const TYPES = [
        'information' => 'ინფორმაციის მიღება',
        'access_copy' => 'მონაცემებზე წვდომა / ასლის მიღება',
        'correction' => 'მონაცემების გასწორება ან განახლება',
        'deletion' => 'მონაცემების წაშლა ან განადგურება',
        'restriction' => 'დამუშავების შეზღუდვა',
        'objection' => 'დამუშავების წინააღმდეგობა',
        'withdraw_consent' => 'თანხმობის გამოხმობა',
        'complaint' => 'პრეტენზია ან საჩივარი',
    ];

    public const STATUSES = [
        'new' => 'ახალი',
        'identity_check' => 'ვინაობის დადასტურება',
        'in_progress' => 'დამუშავებაში',
        'completed' => 'დასრულებული',
        'rejected' => 'დასაბუთებული უარი',
    ];

    protected $fillable = [
        'user_id', 'name', 'phone', 'email', 'request_type', 'details', 'status',
        'verified_at', 'completed_at', 'response_notes', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
