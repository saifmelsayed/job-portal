<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'company_name',
    'industry',
    'company_size',
    'disability_support_policy',
    'overview',
    'facebook_url',
    'x_url',
    'linkedin_url',
    'instagram_url',
])]
class CompanyProfile extends Model
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
}
