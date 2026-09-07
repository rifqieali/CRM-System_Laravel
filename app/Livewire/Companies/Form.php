<?php

namespace App\Livewire\Companies;

use App\Models\Company;
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
#[Title('Company')]
class Form extends Component
{
    public ?int $companyId = null;

    public string $name = '';

    public ?string $industry = null;

    public ?string $size = null;

    public string $website = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $country = '';

    public int $owner_id = 0;

    public string $notes = '';

    /** @var array<int> */
    public array $tag_ids = [];

    public string $newTagName = '';

    public function mount(?Company $company = null): void
    {
        if ($company && $company->exists) {
            GateFacade::authorize('update', $company);

            $this->companyId = $company->id;
            $this->name = $company->name;
            $this->industry = $company->industry;
            $this->size = $company->size;
            $this->website = (string) $company->website;
            $this->phone = (string) $company->phone;
            $this->address = (string) $company->address;
            $this->city = (string) $company->city;
            $this->country = (string) $company->country;
            $this->owner_id = (int) $company->owner_id;
            $this->notes = (string) $company->notes;
            $this->tag_ids = $company->tags()->pluck('tags.id')->all();
        } else {
            GateFacade::authorize('create', Company::class);
            $this->owner_id = (int) auth()->id();
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', Rule::in(['tech', 'finance', 'healthcare', 'retail', 'manufacturing', 'other'])],
            'size' => ['nullable', Rule::in(['1-10', '11-50', '51-200', '201-500', '500+'])],
            'website' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    public function save(bool $addAnother = false): void
    {
        $validated = $this->validate();

        $company = $this->companyId
            ? tap(Company::findOrFail($this->companyId))->update($validated)
            : Company::create($validated);

        $company->tags()->sync($this->tag_ids);

        if ($addAnother) {
            $this->reset(['name', 'industry', 'size', 'website', 'phone', 'address', 'city', 'country', 'notes', 'tag_ids']);
            $this->owner_id = (int) auth()->id();
            session()->flash('status', 'Company disimpan. Tambah lagi.');

            return;
        }

        session()->flash('status', 'Company disimpan.');
        $this->redirectRoute('companies.show', ['company' => $company->id], navigate: true);
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
    public function availableTags(): array
    {
        return Tag::orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function render(): View
    {
        return view('livewire.companies.form');
    }
}
