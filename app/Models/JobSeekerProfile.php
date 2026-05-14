<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'first_name',
    'last_name',
    'full_name',
    'cv_path',
    'gender',
    'disability_type',
])]
class JobSeekerProfile extends Model
{
    /** @var string */
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public URL when the CV is stored on the public disk (under /storage/...).
     */
    public function cvPublicUrl(): ?string
    {
        $path = $this->cv_path;
        if ($path === null || $path === '' || str_contains($path, '..')) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
