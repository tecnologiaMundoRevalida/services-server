<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionReserved extends Model
{
    use HasFactory;

    protected $table = "question_reserved";

    protected $fillable = [
        'question_id',
        'professor_id',
        'reserved',
        'reserved_at',
        'return_at'
    ];
}
