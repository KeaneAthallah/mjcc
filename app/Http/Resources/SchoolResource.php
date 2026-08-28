<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kecamatan_id' => $this->kecamatan_id,
            'kelurahan_id' => $this->kelurahan_id,
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => $this->kecamatan->name),
            'kelurahan' => $this->whenLoaded('kelurahan', fn () => $this->kelurahan->name),
            'name' => $this->name,
            'school_type' => $this->school_type,
            'npsn' => $this->npsn,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'condition' => $this->condition,
            'students_male' => $this->students_male,
            'students_female' => $this->students_female,
            'total_students' => $this->getTotalStudents(),
            'teachers' => $this->teachers,
            'classes' => $this->classes,
            'capacity' => $this->capacity,
            'library_percentage' => $this->floatOrNull('library_percentage'),
            'science_lab_percentage' => $this->floatOrNull('science_lab_percentage'),
            'computer_lab_percentage' => $this->floatOrNull('computer_lab_percentage'),
            'teacher_room_percentage' => $this->floatOrNull('teacher_room_percentage'),
            'toilet_percentage' => $this->floatOrNull('toilet_percentage'),
            'worship_room_percentage' => $this->floatOrNull('worship_room_percentage'),
            'is_active' => $this->is_active,
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function floatOrNull(string $attribute): ?float
    {
        return $this->{$attribute} !== null ? (float) $this->{$attribute} : null;
    }
}
