<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTest extends Model
{
    use HasFactory;

    protected $table = "user_tests";

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'data',
        'qtd_questions'
    ];

    protected $cast = [
        'user_id' => 'number',
        'data' => 'array'
    ];
}
