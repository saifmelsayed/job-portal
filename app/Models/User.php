<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'full_name',
    'email',
    'phone',
    'password',
    'role',
    'status',
    'city',
    'street',
    'profile_photo_path',
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
        return $this->isAdmin() && (bool) ($this->admin?->is_super_admin);
    }

    /**
     * @return HasOne<Admin, $this>
     */
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * @return HasOne<JobSeekerProfile, $this>
     */
    public function jobSeekerProfile(): HasOne
    {
        return $this->hasOne(JobSeekerProfile::class);
    }

    /**
     * @return HasOne<CompanyProfile, $this>
     */
    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
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
     * Public URL for profile photo on the public disk (if stored under storage/app/public).
     */
    public function profilePhotoPublicUrl(): ?string
    {
        $path = $this->profile_photo_path;
        if ($path === null || $path === '' || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('skills.id');
    }

    /**
     * @param  list<string>  $names
     */
    public function syncSkillsFromNames(array $names): void
    {
        $sync = [];
        $seen = [];
        $order = 0;

        foreach ($names as $rawName) {
            if (! is_string($rawName)) {
                continue;
            }
            $trimmed = mb_substr(trim($rawName), 0, 100);
            if ($trimmed === '') {
                continue;
            }
            $key = mb_strtolower($trimmed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $skill = Skill::query()->firstOrCreate(['name' => $trimmed]);
            $sync[$skill->id] = ['sort_order' => $order];
            $order++;
        }

        $this->skills()->sync($sync);
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

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The seeker's primary subscription row used for entitlement (excluding cancelled records).
     *
     * @return HasOne<Subscription, $this>
     */
    public function activeSeekerSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::Suspended->value,
            ])
            ->latestOfMany('id');
    }
}
