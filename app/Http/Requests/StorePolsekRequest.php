<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\Polsek;
use Illuminate\Foundation\Http\FormRequest;

class StorePolsekRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', Polsek::class);
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'name' => ['required', 'string', 'max:255', 'unique:polseks,name'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'personnel_count' => ['required', 'integer', 'min:0'],
            'poskamling_count' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
