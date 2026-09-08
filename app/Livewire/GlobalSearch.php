<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    #[Locked]
    public bool $open = false;

    public function updatedQuery(): void
    {
        $this->open = strlen($this->query) >= 2;
    }

    public function clear(): void
    {
        $this->query = '';
        $this->open = false;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render(): View
    {
        $user = Auth::user();
        $results = ['contacts' => collect(), 'companies' => collect(), 'deals' => collect()];

        if (strlen($this->query) >= 2) {
            $like = '%'.$this->query.'%';

            $results['contacts'] = Contact::scopedTo($user)
                ->where(function ($q) use ($like): void {
                    $q->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->orderBy('first_name')
                ->limit(5)
                ->get();

            $results['companies'] = Company::scopedTo($user)
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(5)
                ->get();

            $results['deals'] = Deal::scopedTo($user)
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(5)
                ->get();
        }

        $total = $results['contacts']->count() + $results['companies']->count() + $results['deals']->count();

        return view('livewire.global-search', [
            'results' => $results,
            'total' => $total,
        ]);
    }
}
