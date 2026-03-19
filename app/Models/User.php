<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fullname', 'username', 'email', 'phone', 'password', 
        'gender', 'birthdate', 'nrc', 'profile_image', 'otp', 'otp_expired_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    protected function profilePhoto(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function ($value) {
                if (!$value) return null;

                // အကယ်၍ $value က URL အပြည့်အစုံ ဖြစ်နေရင် (အရင်က မှားသိမ်းထားတာမျိုး) 
                // ထပ်မပေါင်းအောင် စစ်ပေးမယ်
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }

                $baseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
                return $baseUrl . '/' . ltrim($value, '/');
            }
        );
    }
}
