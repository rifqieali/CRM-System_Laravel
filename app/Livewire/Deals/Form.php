<?php

namespace App\Livewire\Deals;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate as GateFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Deal')]
class Form extends Component
{
    public ?int $dealId = null;

    public string $name = '';

    public string $value = '';

    public string $currency = 'IDR';

    public string $stage = 'prospecting';

    public ?int $probability = null;

    public ?string $expected_close_date = null;

    public int $contact_id = 0;

    public int $company_id = 0;

    public int $owner_id = 0;

    /** @var array<int> */
    public array $tag_ids = [];

    public string $newTagName = '';

    public function mount(?Deal $deal = null): void
    {
        if ($deal && $deal->exists) {
            GateFacade::authorize('update', $deal);

            $this->dealId = $deal->id;
            $this->name = $deal->name;
            $this->value = (string) $deal->value;
            $this->currency = (string) $deal->currency;
            $this->stage = (string) $deal->stage;
            $this->probability = $deal->probability;
            $this->expected_close_date = $deal->expected_close_date instanceof \DateTimeInterface
                ? $deal->expected_close_date->format('Y-m-d')
                : null;
            $this->contact_id = (int) $deal->contact_id;
            $this->company_id = (int) $deal->company_id;
            $this->owner_id = (int) $deal->owner_id;
            $this->tag_ids = $deal->tags()->pluck('tags.id')->all();
        } else {
            GateFacade::authorize('create', Deal::class);
            $this->owner_id = (int) auth()->id();
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'stage' => ['required', Rule::in(Deal::STAGES)],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    public function save(bool $addAnother = false): void
    {
        $validated = $this->validate();
        $validated['value'] = (float) $validated['value'];

        $deal = $this->dealId
            ? tap(Deal::findOrFail($this->dealId))->update($validated)
            : Deal::create($validated);

        $deal->tags()->sync($this->tag_ids);

        if ($addAnother) {
            $this->reset(['name', 'value', 'probability', 'expected_close_date', 'contact_id', 'company_id', 'tag_ids']);
            $this->stage = 'prospecting';
            $this->currency = 'IDR';
            $this->owner_id = (int) auth()->id();
            session()->flash('status', 'Deal disimpan. Tambah lagi.');

            return;
        }

        session()->flash('status', 'Deal disimpan.');
        $this->redirectRoute('deals.show', ['deal' => $deal->id], navigate: true);
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->tag_ids, true)) {
            $this->tag_ids = array_values(array_diff($this->tag_ids, [$tagId]));
        } else {
            $this->tag_ids[] = $tagId;
        }
    }

    public function createTag(): void
    {
        $this->validate(['newTagName' => 'required|string|max:100']);

        $base = Str::slug($this->newTagName);
        $slug = $base;
        $suffix = 2;

        while (Tag::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $tag = Tag::create([
            'name' => $this->newTagName,
            'slug' => $slug,
        ]);

        $this->tag_ids[] = $tag->id;
        $this->reset('newTagName');
    }

    public function updatedContactId(int $contactId): void
    {
        if ($contactId && ! $this->company_id) {
            $companyId = (int) Contact::find($contactId)?->company_id;
            if ($companyId) {
                $this->company_id = $companyId;
            }
        }
    }

    #[Computed]
    public function ownersList(): array
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isManager()) {
            return User::orderBy('name')->get(['id', 'name'])->toArray();
        }

        $ids = array_merge([$user->id], $user->teamIds());

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->toArray();
    }

    #[Computed]
    public function contactsList(): array
    {
        $user = auth()->user();

        $query = Contact::orderBy('first_name')->orderBy('last_name');

        if (! $user->isAdmin() && ! $user->isManager()) {
            $ids = array_merge([$user->id], $user->teamIds());
            $query->whereIn('owner_id', $ids);
        }

        return $query->get(['id', 'first_name', 'last_name', 'company_id'])
            ->map(fn ($c): array => [
                'id' => $c->id,
                'name' => trim($c->first_name.' '.$c->last_name),
                'company_id' => $c->company_id,
            ])
            ->toArray();
    }

    #[Computed]
    public function companiesList(): array
    {
        $user = auth()->user();

        $query = Company::orderBy('name');

        if (! $user->isAdmin() && ! $user->isManager()) {
            $ids = array_merge([$user->id], $user->teamIds());
            $query->whereIn('owner_id', $ids);
        }

        return $query->get(['id', 'name'])->toArray();
    }

    #[Computed]
    public function availableTags(): array
    {
        return Tag::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function render(): View
    {
        return view('livewire.deals.form');
    }
}
