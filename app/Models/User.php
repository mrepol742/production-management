<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    /**
     * Check if the user is a super admin.
     * 
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if the user is an admin.
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Get the deployments assigned to the user.
     * 
     * @return BelongsToMany
     */
    public function deployments(): BelongsToMany
    {
        return $this->belongsToMany(Deployment::class, 'deployment_user');
    }

    /**
     * Get the deployment logs associated with the user.
     * 
     * @return HasMany
     */
    public function deploymentLogs(): HasMany
    {
        return $this->hasMany(DeploymentLog::class, 'user_id');
    }

    public function deploymentJobs(): HasMany
    {
        return $this->hasMany(DeploymentJob::class, 'requested_by');
    }

    /**
     * Get the deployments assigned to the user.
     * 
     * @return BelongsToMany
     */
    public function assignedDeployments(): BelongsToMany
    {
        return $this->belongsToMany(Deployment::class, 'deployment_user');
    }
}
