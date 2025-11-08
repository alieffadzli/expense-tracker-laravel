<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'avatar',
        'password',
        'role',
        'social_auth',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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

    public function organizations ()
    {
        return $this->hasMany(Organization::class, 'author_id', 'id');
    }

    public function branches ()
    {
        return $this->hasMany(Branch::class, 'author_id', 'id');
    }

    public function transactions ()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }

        /**
     * Ambil identifier unik user yang akan disimpan di dalam JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // biasanya ID user
    }

    /**
     * Tambahkan klaim (claims) tambahan jika diperlukan.
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_metadata' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'email_verified_at' => $this->email_verified_at,
                'role' => $this->role,
            ]
        ];
    }

}
