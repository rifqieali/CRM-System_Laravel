<?php

namespace App\Livewire\Shared;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate as GateFacade;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NoteEditor extends Component
{
    #[Locked]
    public string $morphType;

    #[Locked]
    public int $morphId;

    #[Validate('required|string|max:10000')]
    public string $body = '';

    public function mount(string $morphType, int $morphId): void
    {
        $this->morphType = $morphType;
        $this->morphId = $morphId;
    }

    public function save(): void
    {
        $parent = $this->morphType::findOrFail($this->morphId);

        if (method_exists($parent, 'getMorphClass')) {
            // For owner-scoped parents, ensure user can update them; notes follow parent ownership.
            GateFacade::authorize('view', $parent);
        }

        $parent->notes()->create([
            'body' => $this->body,
            'user_id' => auth()->id(),
        ]);

        $this->reset('body');
        session()->flash('status', 'Note ditambahkan.');
    }

    public function render(): View
    {
        $notes = $this->morphType::findOrFail($this->morphId)
            ->notes()
            ->with('user')
            ->latest()
            ->get();

        return view('livewire.shared.note-editor', [
            'notes' => $notes,
        ]);
    }
}
