<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialtyReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'specialty_id',
    ];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class,'specialty_id');
    }
}
