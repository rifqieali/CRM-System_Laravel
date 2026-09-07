<?php

namespace App\Livewire\Deals;

use App\Models\Deal;
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
#[Title('Deals')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $ownerId = null;

    #[Url]
    public ?string $stage = null;

    #[Url]
    public ?int $companyId = null;

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

    public function updatingStage(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyId(): void
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
        $this->reset(['search', 'ownerId', 'stage', 'companyId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function delete(int $dealId): void
    {
        $deal = Deal::findOrFail($dealId);
        GateFacade::authorize('delete', $deal);
        $deal->delete();

        session()->flash('status', 'Deal dihapus.');
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
        $query = Deal::scopedTo(auth()->user())
            ->with(['owner', 'contact', 'company'])
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where('name', 'like', $term);
        }

        if ($this->ownerId) {
            $query->where('owner_id', $this->ownerId);
        }

        if ($this->stage) {
            $query->where('stage', $this->stage);
        }

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        if ($this->dateFrom) {
            $query->whereDate('expected_close_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('expected_close_date', '<=', $this->dateTo);
        }

        $deals = $query->paginate(15);

        return view('livewire.deals.index', [
            'deals' => $deals,
        ]);
    }
}
