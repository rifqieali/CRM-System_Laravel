<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', Activity::TYPES)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:due_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Tipe aktivitas wajib dipilih.',
            'type.in' => 'Tipe aktivitas tidak valid.',
            'subject.required' => 'Subjek aktivitas wajib diisi.',
            'subject.max' => 'Subjek maksimal 255 karakter.',
            'due_at.date' => 'Format tanggal jatuh tempo tidak valid.',
            'completed_at.date' => 'Format tanggal selesai tidak valid.',
            'completed_at.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal jatuh tempo.',
        ];
    }
}
