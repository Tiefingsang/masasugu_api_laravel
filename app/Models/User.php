<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'slug',
        'role',
        'status',
        'company_id',
        'is_verified',
        'avatar',
        'bio',
        'country',
        'city',
        'address',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
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
    // Génération automatique du slug à partir du nom
    protected static function boot(){
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->slug)) {
                $user->slug = Str::slug($user->name . '-' . Str::random(6));
            }
        });
    }

    public function company(){
        return $this->hasOne(Company::class);
    }

    

    /**
     * Détermine si l’utilisateur est administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Détermine si l’utilisateur est vendeur.
     */
    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    /**
     * Détermine si l’utilisateur est acheteur.
     */
    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

}
