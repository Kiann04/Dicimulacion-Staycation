<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'usertype'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function updateProfilePhoto($photo)
    {
        // Ensure folder exists
        $destination = public_path('uploads/profile_photos');
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        // Save file with unique name
        $filename = time() . '_' . $photo->getClientOriginalName();
        $photo->move($destination, $filename);

        // Save relative path in DB
        $this->update(['photo' => 'uploads/profile_photos/' . $filename]);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function getProfilePhotoUrlAttribute()
{
    return $this->photo && file_exists(storage_path('app/public/'.$this->photo)) 
        ? asset('storage/'.$this->photo) 
        : asset('Assets/default.png');
}

}
