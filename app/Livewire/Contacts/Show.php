<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate as GateFacade;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Contact')]
class Show extends Component
{
    public Contact $contact;

    public string $activeTab = 'overview';

    public ?string $activityTypeFilter = null;

    public function mount(Contact $contact): void
    {
        GateFacade::authorize('view', $contact);
        $this->contact = $contact->load(['owner', 'company', 'tags']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function delete(): void
    {
        GateFacade::authorize('delete', $this->contact);
        $this->contact->delete();
        session()->flash('status', 'Contact dihapus.');

        $this->redirectRoute('contacts.index', navigate: true);
    }

    public function detachTag(int $tagId): void
    {
        GateFacade::authorize('update', $this->contact);
        $this->contact->tags()->detach($tagId);
        $this->contact->refresh();
    }

    #[Computed]
    public function activities(): Collection
    {
        $query = $this->contact->activities()->with('user')->latest();

        if ($this->activityTypeFilter) {
            $query->where('type', $this->activityTypeFilter);
        }

        return $query->get()
            ->filter(fn ($a): bool => (bool) auth()->user()->can('view', $a))
            ->values();
    }

    #[Computed]
    public function notes()
    {
        return $this->contact->notes()->with('user')->latest()->get();
    }

    #[Computed]
    public function deals()
    {
        return $this->contact->deals()->with(['company', 'owner'])->latest()->get();
    }

    public function render(): View
    {
        return view('livewire.contacts.show');
    }
}
