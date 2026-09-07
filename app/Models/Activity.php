<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    public const TYPE_CALL = 'call';

    public const TYPE_EMAIL = 'email';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_TASK = 'task';

    public const TYPES = [
        self::TYPE_CALL,
        self::TYPE_EMAIL,
        self::TYPE_MEETING,
        self::TYPE_TASK,
    ];

    protected $fillable = [
        'type',
        'subject',
        'description',
        'due_at',
        'completed_at',
        'user_id',
        'activityable_type',
        'activityable_id',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            $activity->guardMorphType();
        });

        static::updating(function (Activity $activity): void {
            if ($activity->isDirty('activityable_type')) {
                $activity->guardMorphType();
            }
        });
    }

    protected function guardMorphType(): void
    {
        $allowed = [Contact::class, Company::class, Deal::class];

        if (! in_array($this->activityable_type, $allowed, true)) {
            throw new InvalidArgumentException(
                'Activity activityable_type must be one of: '.implode(', ', $allowed)
            );
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityable(): MorphTo
    {
        return $this->morphTo();
    }
}
