<?php

namespace App\Models;

use App\Models\Concerns\HasOwner;
use App\Models\Concerns\HasTags;
use App\Models\Concerns\ScopedToUser;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory, HasOwner, HasTags, ScopedToUser, SoftDeletes;

    public const STAGE_PROSPECTING = 'prospecting';

    public const STAGE_QUALIFICATION = 'qualification';

    public const STAGE_PROPOSAL = 'proposal';

    public const STAGE_NEGOTIATION = 'negotiation';

    public const STAGE_WON = 'won';

    public const STAGE_LOST = 'lost';

    public const STAGES = [
        self::STAGE_PROSPECTING,
        self::STAGE_QUALIFICATION,
        self::STAGE_PROPOSAL,
        self::STAGE_NEGOTIATION,
        self::STAGE_WON,
        self::STAGE_LOST,
    ];

    public const CLOSED_STAGES = [self::STAGE_WON, self::STAGE_LOST];

    protected $fillable = [
        'name',
        'value',
        'currency',
        'stage',
        'probability',
        'expected_close_date',
        'closed_at',
        'contact_id',
        'company_id',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Deal $deal): void {
            if (! in_array($deal->stage, self::STAGES, true)) {
                throw new InvalidArgumentException("Invalid deal stage: {$deal->stage}");
            }

            if (in_array($deal->stage, self::CLOSED_STAGES, true)) {
                $deal->closed_at ??= now();
            } else {
                $deal->closed_at = null;
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function isClosed(): bool
    {
        return in_array($this->stage, self::CLOSED_STAGES, true);
    }
}
