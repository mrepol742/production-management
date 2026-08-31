<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    use HasFactory;

    const TYPE_LARAVEL = 'laravel';
    const TYPE_NODE = 'node';

    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_UNKNOWN = 'unknown';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'path',
        'repo_url',
        'deploy_command',
        'branch',
        'ssh_key_name',
        'ssh_private_key',
        'ssh_private_key_path',
        'ssh_config',
        'ssh_config_path',
        'pm2_name',
        'pm2_instances',
        'pm2_home',
        'status',
        'created_by',
        'updated_by',
        'last_deployed_at',
    ];

    
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_deployed_at' => 'datetime',
            'ssh_private_key' => 'encrypted',
            'ssh_config' => 'encrypted',
        ];
    }

    /**
     * Get the user who created this deployment.
     * 
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the deployment logs.
     * 
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(DeploymentLog::class)->latest();
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(DeploymentJob::class)->latest();
    }

    /**
     * Get the assigned admins for this deployment.
     * 
     * @return BelongsToMany
     */
    public function assignedAdmins(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Check if the given user is assigned to this deployment.
     * 
     * @return bool
     */
    public function isAssignedTo(User $user): bool
    {
        return $this->assignedAdmins()->whereKey($user->id)->exists();
    }

    /**
     * Check if this deployment is a Laravel deployment.
     * 
     * @return bool
     */
    public function isLaravel(): bool
    {
        return $this->type === self::TYPE_LARAVEL;
    }

    /**
     * Check if this deployment is a Node.js deployment.
     * 
     * @return bool
     */
    public function isNode(): bool
    {
        return $this->type === self::TYPE_NODE;
    }

    /**
     * Get the path to the .env file.
     * 
     * @return string
     */
    public function envPath(): string
    {
        return rtrim($this->path, '/').'/.env';
    }

    /**
     * Determine whether this deployment has an SSH key configured for Git operations.
     */
    public function hasStoredSshKey(): bool
    {
        return filled($this->ssh_private_key);
    }

    /**
     * Determine whether this deployment has an SSH config configured for Git operations.
     */
    public function hasStoredSshConfig(): bool
    {
        return filled($this->ssh_config);
    }
}
