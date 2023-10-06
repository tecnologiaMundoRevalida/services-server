<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'test_id',
        'medicine_area_id',
        'time',
        'active',
        'free'
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function medicineArea()
    {
        return $this->belongsTo(MedicineArea::class);
    }

    public function orientations()
    {
        return $this->hasMany(Orientation::class)->where("instruction",0)->orderBy("order");
    }

    public function instructions()
    {
        return $this->hasMany(Orientation::class)->where("instruction",1);
    }

    public function printeds()
    {
        return $this->hasMany(Printed::class)->where("summary",0);
    }

    public function summaries()
    {
        return $this->hasMany(Printed::class)->where("summary",1);
    }

    public function images()
    {
        return $this->hasMany(ChecklistImage::class);
    }

    public function trainings()
    {
        return $this->hasMany(Training::class,"checklist_id","id");
    }

    public function items()
    {
        return $this->belongsToMany(Item::class)->orderBy("order","asc");
    }

}
