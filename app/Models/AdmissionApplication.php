<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdmissionApplication extends Model
{
    protected $fillable = ['guardian_user_id', 'parent_name', 'phone', 'child_name', 'birth_year', 'preferred_group', 'academic_year', 'wants_tour', 'preferred_tour_date', 'comment', 'status', 'source'];
    protected function casts(): array { return ['wants_tour' => 'boolean', 'preferred_tour_date' => 'date']; }
}
