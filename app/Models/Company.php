<?php

namespace App\Models;

use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasTags;
use App\Models\Concerns\ScopedToUser;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasOwner, HasTags, ScopedToUser, SoftDeletes;

    protected $fillable = [
        'name',
        'industry',
        'size',
        'website',
        'phone',
        'address',
        'city',
        'country',
        'owner_id',
        'notes',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
