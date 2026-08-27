<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKecamatanRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('kecamatan'));
    }

    public function rules(): array
    {
        $coordinates = $this->coordinateRules(false);

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('kecamatans', 'name')->ignore($this->route('kecamatan'))],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('kecamatans', 'code')->ignore($this->route('kecamatan'))],
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
