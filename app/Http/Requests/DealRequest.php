<?php

namespace App\Http\Requests;

use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create', Deal::class);
        }

        $deal = $this->route('deal');

        return $this->user()->can('update', $deal);
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
}
