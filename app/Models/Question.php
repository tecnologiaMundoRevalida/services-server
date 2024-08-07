<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'explanation',
        'discursive_response',
        'is_discursive',
        'is_new',
        'is_annulled',
        // 'institution_id',
        // 'year_id',
        'active',
        'test_id',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function year()
    {
        return $this->belongsTo(Year::class);
    }

    public function themeReference()
    {
        return $this->hasOne(ThemeReference::class,'question_id','id');
    }

    public function medicineAreaReference()
    {
        return $this->hasOne(MedicineAreaReference::class,'question_id','id');
    }

    public function alternatives()
    {
        return $this->hasMany(Alternative::class);
    }

    public function images()
    {
        return $this->hasMany(QuestionImage::class);
    }

    public function areas()
    {
        return $this->belongsToMany(MedicineArea::class, 'medicine_area_references', 'question_id', 'medicine_area_id');
    }

    public function areaByQuestion($question_id)
    {
        $areaReferece = MedicineAreaReference::where('question_id', $question_id)->first();
        if($areaReferece){
            return MedicineArea::find($areaReferece->medicine_area_id);
        }
        return null;
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'specialty_references', 'question_id', 'specialty_id');
    }

    public function specialtyByQuestion($question_id)
    {
        $specialtyReference = SpecialtyReference::where('question_id', $question_id)->first();
        if($specialtyReference){
            return Specialty::find($specialtyReference->specialty_id);
        }
        return null;
    }

    public function specialtyReference()
    {
        return $this->hasOne(SpecialtyReference::class,'question_id','id');
    }

    public function themes()
    {
        return $this->belongsToMany(Theme::class, 'theme_references', 'question_id', 'theme_id');
    }

    public function mockTests()
    {
        return $this->belongsToMany(MockTest::class, 'mock_test_references', 'question_id', 'mock_test_id');
    }

    public function favorite()
    {
        return $this->hasOne(QuestionUserFavorite::class, 'question_id', 'id');
    }
}
