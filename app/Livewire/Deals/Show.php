<?php

namespace App\Livewire\Deals;

use App\Models\Deal;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate as GateFacade;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Deal')]
class Show extends Component
{
    public Deal $deal;

    public string $activeTab = 'overview';

    public ?string $activityTypeFilter = null;

    public function mount(Deal $deal): void
    {
        GateFacade::authorize('view', $deal);
        $this->deal = $deal->load(['owner', 'contact', 'company', 'tags']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function delete(): void
    {
        GateFacade::authorize('delete', $this->deal);
        $this->deal->delete();
        session()->flash('status', 'Deal dihapus.');

        $this->redirectRoute('deals.index', navigate: true);
    }

    public function activities(): Collection
    {
        $query = $this->deal->activities()->with('user')->latest();

        if ($this->activityTypeFilter) {
            $query->where('type', $this->activityTypeFilter);
        }

        return $query->get()
            ->filter(fn ($a): bool => (bool) auth()->user()->can('view', $a))
            ->values();
    }

    public function notes(): Collection
    {
        return $this->deal->notes()->with('user')->latest()->get();
    }

    public function render(): View
    {
        return view('livewire.deals.show');
    }
}
