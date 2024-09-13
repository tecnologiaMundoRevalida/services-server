<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'year_id',
        'type',
        'amount_questions',
        'status',
        'amount_questions_processed',
        'file_path',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function year()
    {
        return $this->belongsTo(Year::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }
}
