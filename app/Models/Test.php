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
        'released_to_comment',
        'amount_questions_processed',
        'amount_tags_processed',
        'file_path',
        'tag_generation_status'
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

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
