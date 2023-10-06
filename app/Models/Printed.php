<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Printed extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'checklist_id',
        'order',
        'summary',
        'active',
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}
