<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'theme_id',
    ];

    public function theme()
    {
        return $this->hasOne(Theme::class,'id','theme_id');
    }
}
