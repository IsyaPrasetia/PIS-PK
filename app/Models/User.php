<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'kecamatan', 'desa', 'rw', 'rt', 'created_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => UserRole::class,
        ];
    }

    public function role(): UserRole
    {
        return $this->role ?? UserRole::Petugas;
    }

    public function isSuperadmin(): bool
    {
        return $this->role() === UserRole::Superadmin;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role() !== UserRole::Petugas;
    }

    /**
     * Apakah boleh mengelola akun lain?
     * Superadmin bisa semua; admin wilayah hanya untuk bawahannya.
     */
    public function canManageUsers(): bool
    {
        return in_array($this->role(), [UserRole::Superadmin, UserRole::AdminWilayah], true);
    }

    /**
     * Apakah role $target lebih rendah (perlu dikelola) dibanding pengguna ini?
     */
    public function outranks(UserRole $target): bool
    {
        return $this->role()->level() < $target->level();
    }

    /**
     * Pengguna ini bisa membuat akun dengan role tertentu?
     */
    public function canCreateRole(UserRole $target): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if (! $this->outranks($target)) {
            return false;
        }

        return $target->level() >= UserRole::AdminRW->level();
    }

    /**
     * Judul agent/scope ringkas untuk ditampilkan (mis. "Admin RW · Cibiru RW 05").
     */
    public function scopeLabel(): string
    {
        $parts = array_filter([
            $this->kecamatan,
            $this->desa,
            $this->rw ? 'RW '.$this->rw : null,
            $this->rt ? 'RT '.$this->rt : null,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : 'Semua Wilayah';
    }

    public function matchesFamily(Family $family): bool
    {
        return match ($this->role()) {
            UserRole::Superadmin => true,
            UserRole::AdminWilayah => $this->matches($family->kecamatan, null, null, null),
            UserRole::AdminRW => $this->matches($family->kecamatan, $family->desa, $family->rw, null),
            UserRole::AdminRT, UserRole::Petugas => $this->matches($family->kecamatan, $family->desa, $family->rw, $family->rt),
        };
    }

    public function canViewFamily(Family $family): bool
    {
        return $this->matchesFamily($family);
    }

    public function canCreateFamily(): bool
    {
        return true;
    }

    /**
     * Petugas: hanya data sendiri yang dibuat dalam 24 jam.
     * Admin (ke atas): boleh selama data masuk scope.
     */
    public function canEditFamily(Family $family): bool
    {
        if (! $this->matchesFamily($family)) {
            return false;
        }

        if ($this->role() === UserRole::Petugas) {
            return $family->created_by === $this->id
                && $family->created_at
                && $family->created_at->diffInHours(now()) < 24;
        }

        return true;
    }

    /**
     * Implementasi inti pencocokan wilayah.
     */
    private function matches(?string $kecamatan, ?string $desa, ?string $rw, ?string $rt): bool
    {
        $actual = [
            'kecamatan' => $kecamatan,
            'desa' => $desa,
            'rw' => $rw,
            'rt' => $rt,
        ];
        $expected = [
            'kecamatan' => $this->kecamatan,
            'desa' => $this->desa,
            'rw' => $this->rw,
            'rt' => $this->rt,
        ];

        foreach ($expected as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (($actual[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }
}
