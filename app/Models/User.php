<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'nip',
        'role',
        'password_hash',
        'must_change_password',
        'is_active',
        'auth_provider',
        'auth_provider_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get password for authentication.
     */
    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    /**
     * Setter mutator for password_hash.
     */
    public function setPasswordAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['password_hash'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }

    /**
     * Filament display name.
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Check if user can access Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active || $this->deleted_at !== null) {
            return false;
        }

        // Super Admin, Admin, dan Pegawai aktif dapat mengakses panel
        return in_array($this->role, ['super_admin', 'admin', 'pegawai'], true);
    }

    /**
     * Role helper checks.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    /**
     * Check granular permission based on RBAC matrix.
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || $this->deleted_at !== null) {
            return false;
        }

        $superAdminPermissions = [
            'monitor.view',
            'evidence.view_link',
            'evidence.delete',
            'evidence.verify',
            'evidence.manage_markup',
            'lk.manage',
            'lk.indicator_manage',
            'lk.manage_drive_links',
            'audit.view',
            'settings.manage',
            'user.manage',
            'match.manual_create',
            'match.manual_delete',
            'match.rematch',
        ];

        $adminPermissions = [
            'monitor.view',
            'evidence.search',
            'evidence.view_link',
            'evidence.upload',
            'evidence.verify',
            'evidence.manage_markup',
            'lk.manage_drive_links',
            'drive.sync',
            'match.manual_create',
            'match.manual_delete',
            'match.rematch',
        ];

        $pegawaiPermissions = [
            'monitor.view',
            'evidence.search',
            'evidence.view_link',
            'evidence.upload',
            'evidence.revise_own',
        ];

        if ($this->isSuperAdmin()) {
            return in_array($permission, $superAdminPermissions, true);
        }

        if ($this->isAdmin()) {
            return in_array($permission, $adminPermissions, true);
        }

        if ($this->isPegawai()) {
            return in_array($permission, $pegawaiPermissions, true);
        }

        return false;
    }
}
