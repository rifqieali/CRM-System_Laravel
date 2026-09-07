<?php

namespace App\Livewire\Shared;

use App\Models\Activity;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate as GateFacade;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LogActivityModal extends Component
{
    #[Locked]
    public string $morphType;

    #[Locked]
    public int $morphId;

    public bool $open = false;

    #[Validate('required|in:'.Activity::TYPE_CALL.','.Activity::TYPE_EMAIL.','.Activity::TYPE_MEETING.','.Activity::TYPE_TASK)]
    public string $type = Activity::TYPE_CALL;

    #[Validate('required|string|max:255')]
    public string $subject = '';

    public string $description = '';

    public ?string $due_at = null;

    public ?string $completed_at = null;

    public function mount(string $morphType, int $morphId): void
    {
        $this->morphType = $morphType;
        $this->morphId = $morphId;
    }

    public function openModal(): void
    {
        $this->reset(['subject', 'description', 'due_at', 'completed_at']);
        $this->type = Activity::TYPE_CALL;
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function save(): void
    {
        $parent = $this->morphType::findOrFail($this->morphId);
        GateFacade::authorize('create', Activity::class);

        $parent->activities()->create([
            'type' => $this->type,
            'subject' => $this->subject,
            'description' => $this->description !== '' ? $this->description : null,
            'due_at' => $this->due_at ?: null,
            'completed_at' => $this->completed_at ?: null,
            'user_id' => auth()->id(),
        ]);

        $this->closeModal();
        session()->flash('status', 'Activity dicatat.');

        $this->dispatch('activity-created');
    }

    public function render(): View
    {
        return view('livewire.shared.log-activity-modal');
    }
}
