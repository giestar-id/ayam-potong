<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->id === (int) static::min('id');
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }
}
