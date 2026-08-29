<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\Kecamatan;
use Illuminate\Foundation\Http\FormRequest;

class StoreKecamatanRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', Kecamatan::class);
    }

    public function rules(): array
    {
        $coordinates = $this->coordinateRules(false);

        return [
            'name' => ['required', 'string', 'max:255', 'unique:kecamatans,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:kecamatans,code'],
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
