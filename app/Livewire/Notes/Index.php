<?php

declare(strict_types=1);

namespace App\Livewire\Notes;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'entity', except: '')]
    public string $entityType = '';

    #[Url(as: 'owner', except: '')]
    public string $ownerId = '';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEntityType(): void
    {
        $this->resetPage();
    }

    public function updatedOwnerId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'entityType', 'ownerId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function delete(int $noteId): void
    {
        $note = Note::findOrFail($noteId);
        $this->authorize('delete', $note);
        $note->delete();
        session()->flash('status', 'Note dihapus.');
        $this->resetPage();
    }

    #[Computed]
    public function ownersList(): array
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Admin', 'Manager'])) {
            return User::orderBy('name')->get(['id', 'name'])->all();
        }

        $ids = [$user->id];

        if ($user->manager_id !== null) {
            $ids = array_merge($ids, User::where('manager_id', $user->manager_id)->pluck('id')->all());
        }

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->all();
    }

    public function render(): View
    {
        $user = Auth::user();
        $notes = $this->buildQuery($user)->paginate(15);

        return view('livewire.notes.index', [
            'notes' => $notes,
            'entityTypes' => [
                Contact::class => 'Contact',
                Company::class => 'Company',
                Deal::class => 'Deal',
            ],
        ]);
    }

    private function buildQuery(User $user): Builder
    {
        $query = Note::with(['user', 'noteable'])
            ->latest('created_at');

        $this->applyScope($query, $user);
        $this->applySearch($query);
        $this->applyEntityType($query);
        $this->applyOwner($query);
        $this->applyDateRange($query);

        return $query;
    }

    private function applyScope(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['Admin', 'Manager'])) {
            return;
        }

        $query->where(function (Builder $q) use ($user) {
            $this->scopeMorphOwner($q, $user, Contact::class);
            $this->scopeMorphOwner($q, $user, Company::class);
            $this->scopeMorphOwner($q, $user, Deal::class);
        });
    }

    private function scopeMorphOwner(Builder $query, User $user, string $morphClass): void
    {
        $query->orWhereHasMorph(
            'noteable',
            $morphClass,
            function (Builder $q) use ($user) {
                $q->where(function (Builder $sub) use ($user) {
                    $sub->where('owner_id', $user->id);

                    if ($user->manager_id !== null) {
                        $sub->orWhereIn('owner_id', function ($subQuery) use ($user) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('manager_id', $user->manager_id);
                        });
                    }
                });
            }
        );
    }

    private function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $term = '%'.$this->search.'%';
        $query->where('body', 'like', $term);
    }

    private function applyEntityType(Builder $query): void
    {
        $allowed = [Contact::class, Company::class, Deal::class];

        if ($this->entityType === '' || ! in_array($this->entityType, $allowed, true)) {
            return;
        }

        $query->where('noteable_type', $this->entityType);
    }

    private function applyOwner(Builder $query): void
    {
        if ($this->ownerId === '') {
            return;
        }

        $query->where('user_id', $this->ownerId);
    }

    private function applyDateRange(Builder $query): void
    {
        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
    }
}
