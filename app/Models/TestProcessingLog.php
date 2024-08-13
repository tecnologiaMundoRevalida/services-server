<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestProcessingLog extends Model
{
    use HasFactory;

    protected $table = "test_processing_logs";

    protected $fillable = [
        'test_id',
        'question_id',
        'number_question',
        'log'
    ];
}
