<?php

namespace App\Http\Requests\Api;

use App\Models\SosAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConstrainedSosAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reportConstraint', $this->route('sos'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'constraint_type' => ['required', Rule::in([SosAlert::CONSTRAINT_CANNOT_REACH, SosAlert::CONSTRAINT_DELAYED])],
            'constraint_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
