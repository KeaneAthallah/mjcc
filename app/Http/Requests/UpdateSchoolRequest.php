<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCoordinates;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('school'));
    }

    public function rules(): array
    {
        $c = $this->coordinateRules(false);
        $school = $this->route('school');

        return [
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kelurahan_id' => ['nullable', 'exists:kelurahans,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('schools', 'name')->ignore($school)],
            'school_type' => ['required', 'in:' . School::TYPE_SD . ',' . School::TYPE_SMP],
            'npsn' => ['nullable', 'string', 'max:20', Rule::unique('schools', 'npsn')->ignore($school)],
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
