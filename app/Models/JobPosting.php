<?php

namespace App\Models;

use App\Enums\JobWorkType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'title',
    'description',
    'requirements',
    'qualification',
    'location',
    'type',
    'approved_disability',
    'category',
    'skills',
])]
class JobPosting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => JobWorkType::class,
            'approved_disability' => 'array',
            'skills' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Job postings whose owning company account is active (public directory).
     *
     * @param  Builder<JobPosting>  $query
     * @return Builder<JobPosting>
     */
    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->whereHas('user', static function (Builder $userQuery): void {
            $userQuery->where('status', 'active');
        });
    }
}
