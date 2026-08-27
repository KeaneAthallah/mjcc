<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\HealthFacility;
use Illuminate\Foundation\Http\FormRequest;

class StoreHealthFacilityRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', HealthFacility::class);
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', 'in:' . implode(',', ['Puskesmas', 'Pustu', 'Rumah Sakit', 'Posyandu'])],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'condition' => ['nullable', 'string', 'max:50'],
            'beds' => ['required', 'integer', 'min:0'],
            'doctors' => ['required', 'integer', 'min:0'],
            'nurses' => ['required', 'integer', 'min:0'],
            'midwives' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
        ];
    }
}
