<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate as GateFacade;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Contacts')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $ownerId = null;

    #[Url]
    public ?string $source = null;

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingOwnerId(): void
    {
        $this->resetPage();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'ownerId', 'source', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function delete(int $contactId): void
    {
        $contact = Contact::findOrFail($contactId);
        GateFacade::authorize('delete', $contact);
        $contact->delete();

        session()->flash('status', 'Contact dihapus.');
    }

    #[Computed]
    public function owners(): array
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return User::orderBy('name')->get(['id', 'name'])->toArray();
        }

        $ids = array_merge([$user->id], $user->teamIds());

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function render(): View
    {
        $query = Contact::scopedTo(auth()->user())
            ->with(['owner', 'company'])
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($this->ownerId) {
            $query->where('owner_id', $this->ownerId);
        }

        if ($this->source) {
            $query->where('source', $this->source);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $contacts = $query->paginate(15);

        return view('livewire.contacts.index', [
            'contacts' => $contacts,
        ]);
    }
}
