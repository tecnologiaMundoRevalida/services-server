<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'nickname',
        'password',
        'expiration_date',
        'date_first_login',
        'date_terms_of_use',
        'image',
        'permission',
        'active',
        'document_number',
        'phone',
        'type_id',
        'medicine_area_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function profiles()
    {
        return $this->belongsToMany(Profile::class)->withPivot(["expiration_date","experimental"]);
    }

    public function studentTrainings()
    {
        return $this->hasMany(Training::class,"student_id","id");
    }

    public function instructorTrainings()
    {
        return $this->hasMany(Training::class,"instructor_id","id");
    }
}
