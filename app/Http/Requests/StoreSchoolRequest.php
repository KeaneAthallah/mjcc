<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('create', School::class);
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kelurahan_id' => ['nullable', 'exists:kelurahans,id'],
            'name' => ['required', 'string', 'max:255', 'unique:schools,name'],
            'school_type' => ['required', 'in:'.implode(',', School::SCHOOL_TYPES)],
            'npsn' => ['nullable', 'string', 'max:20', 'unique:schools,npsn'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => $c['latitude'],
            'longitude' => $c['longitude'],
            'condition' => ['nullable', 'string', 'max:50'],
            'students_male' => ['required', 'integer', 'min:0'],
            'students_female' => ['required', 'integer', 'min:0'],
            'teachers' => ['required', 'integer', 'min:0'],
            'classes' => ['required', 'integer', 'min:0'],
            'capacity' => ['required', 'integer', 'min:0'],
            'library_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'science_lab_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'computer_lab_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'teacher_room_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'toilet_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'worship_room_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'is_active' => ['nullable', 'boolean'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
        ];
    }
}
