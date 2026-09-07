<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate as GateFacade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Company')]
class Show extends Component
{
    public Company $company;

    public string $activeTab = 'overview';

    public ?string $activityTypeFilter = null;

    public function mount(Company $company): void
    {
        GateFacade::authorize('view', $company);
        $this->company = $company->load(['owner', 'tags']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function delete(): void
    {
        GateFacade::authorize('delete', $this->company);
        $this->company->delete();
        session()->flash('status', 'Company dihapus.');

        $this->redirectRoute('companies.index', navigate: true);
    }

    public function detachTag(int $tagId): void
    {
        GateFacade::authorize('update', $this->company);
        $this->company->tags()->detach($tagId);
        $this->company->refresh();
    }

    public function activities(): Collection
    {
        $query = $this->company->activities()->with('user')->latest();

        if ($this->activityTypeFilter) {
            $query->where('type', $this->activityTypeFilter);
        }

        return $query->get()
            ->filter(fn ($a): bool => (bool) auth()->user()->can('view', $a))
            ->values();
    }

    public function notes(): Collection
    {
        return $this->company->notes()->with('user')->latest()->get();
    }

    public function contacts()
    {
        return $this->company->contacts()->with('owner')->latest()->get();
    }

    public function deals()
    {
        return $this->company->deals()->with(['contact', 'owner'])->latest()->get();
    }

    public function render(): View
    {
        return view('livewire.companies.show');
    }
}
