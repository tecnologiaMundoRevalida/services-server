<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineAreaReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'medicine_area_id',
    ];


    public function medicineArea()
    {
        return $this->belongsTo(MedicineArea::class,'medicine_area_id');
    }
}
