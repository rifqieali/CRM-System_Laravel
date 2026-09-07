<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    protected $fillable = [
        'body',
        'user_id',
        'noteable_type',
        'noteable_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Note $note): void {
            $note->guardMorphType();
        });

        static::updating(function (Note $note): void {
            if ($note->isDirty('noteable_type')) {
                $note->guardMorphType();
            }
        });
    }

    protected function guardMorphType(): void
    {
        $allowed = [Contact::class, Company::class, Deal::class];

        if (! in_array($this->noteable_type, $allowed, true)) {
            throw new InvalidArgumentException(
                'Note noteable_type must be one of: '.implode(', ', $allowed)
            );
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
