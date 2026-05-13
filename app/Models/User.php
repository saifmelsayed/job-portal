<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'first_name',
    'last_name',
    'full_name',
    'cv_path',
    'company_name',
    'industry',
    'company_size',
    'email',
    'phone',
    'password',
    'role',
    'status',
    'gender',
    'city',
    'street',
    'profile_photo_path',
    'is_super_admin',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_super_admin' => 'boolean',
        ];
    }

    public function isJobSeeker(): bool
    {
        return $this->role === UserRole::JobSeeker;
    }

    public function isCompany(): bool
    {
        return $this->role === UserRole::Company;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->is_super_admin;
    }

    /**
     * @return HasMany<JobPosting, $this>
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * @return HasMany<UserSkill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<UserEducation, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(UserEducation::class)->orderByDesc('starts_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<UserExperience, $this>
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(UserExperience::class)->orderByDesc('starts_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<UserCertificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(UserCertificate::class)->orderByDesc('issued_at')->orderByDesc('id');
    }
}
