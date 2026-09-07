<?php

declare(strict_types=1);

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
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

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'entity', except: '')]
    public string $entityType = '';

    #[Url(as: 'owner', except: '')]
    public string $ownerId = '';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(as: 'due', except: '')]
    public string $dueFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
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

    public function updatedDueFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'type', 'entityType', 'ownerId', 'dateFrom', 'dateTo', 'dueFilter']);
        $this->resetPage();
    }

    public function delete(int $activityId): void
    {
        $activity = Activity::findOrFail($activityId);
        $this->authorize('delete', $activity);
        $activity->delete();
        session()->flash('status', 'Activity dihapus.');
        $this->resetPage();
    }

    public function markComplete(int $activityId): void
    {
        $activity = Activity::findOrFail($activityId);
        $this->authorize('update', $activity);
        $activity->update(['completed_at' => now()]);
        session()->flash('status', 'Activity ditandai selesai.');
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
        $activities = $this->buildQuery($user)->paginate(15);

        return view('livewire.activities.index', [
            'activities' => $activities,
            'types' => Activity::TYPES,
            'entityTypes' => [
                Contact::class => 'Contact',
                Company::class => 'Company',
                Deal::class => 'Deal',
            ],
        ]);
    }

    private function buildQuery(User $user): Builder
    {
        $query = Activity::with(['user', 'activityable'])
            ->latest('created_at');

        $this->applyScope($query, $user);
        $this->applySearch($query);
        $this->applyType($query);
        $this->applyEntityType($query);
        $this->applyOwner($query);
        $this->applyDateRange($query);
        $this->applyDueFilter($query, $user);

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
            'activityable',
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
        $query->where(function (Builder $q) use ($term) {
            $q->where('subject', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    private function applyType(Builder $query): void
    {
        if ($this->type === '' || ! in_array($this->type, Activity::TYPES, true)) {
            return;
        }

        $query->where('type', $this->type);
    }

    private function applyEntityType(Builder $query): void
    {
        $allowed = [Contact::class, Company::class, Deal::class];

        if ($this->entityType === '' || ! in_array($this->entityType, $allowed, true)) {
            return;
        }

        $query->where('activityable_type', $this->entityType);
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

    private function applyDueFilter(Builder $query, User $user): void
    {
        $now = now();

        match ($this->dueFilter) {
            'overdue' => $query->whereNotNull('due_at')
                ->whereNull('completed_at')
                ->where('due_at', '<', $now),
            'today' => $query->whereNotNull('due_at')
                ->whereNull('completed_at')
                ->whereDate('due_at', $now->toDateString()),
            'week' => $query->whereNotNull('due_at')
                ->whereNull('completed_at')
                ->whereBetween('due_at', [$now, $now->copy()->addDays(7)]),
            'completed' => $query->whereNotNull('completed_at'),
            default => null,
        };
    }
}
