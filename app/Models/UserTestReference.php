<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTestReference extends Model
{
    use HasFactory;

    protected $table = "user_test_references";

    protected $fillable = [
        'question_id',
        'user_test_id'
    ];
}
