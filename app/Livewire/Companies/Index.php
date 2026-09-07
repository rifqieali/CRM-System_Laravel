<?php

namespace App\Livewire\Companies;

use App\Models\Company;
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
#[Title('Companies')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $ownerId = null;

    #[Url]
    public ?string $industry = null;

    #[Url]
    public ?string $size = null;

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

    public function updatingIndustry(): void
    {
        $this->resetPage();
    }

    public function updatingSize(): void
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
        $this->reset(['search', 'ownerId', 'industry', 'size', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function delete(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        GateFacade::authorize('delete', $company);
        $company->delete();

        session()->flash('status', 'Company dihapus.');
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
        $query = Company::scopedTo(auth()->user())
            ->with('owner')
            ->orderByDesc('created_at');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('website', 'like', $term);
            });
        }

        if ($this->ownerId) {
            $query->where('owner_id', $this->ownerId);
        }

        if ($this->industry) {
            $query->where('industry', $this->industry);
        }

        if ($this->size) {
            $query->where('size', $this->size);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $companies = $query->paginate(15);

        return view('livewire.companies.index', [
            'companies' => $companies,
        ]);
    }
}
