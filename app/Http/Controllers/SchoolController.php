<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', School::class);

        $schools = School::query()
            ->with(['kecamatan:id,name', 'kelurahan:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('npsn', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('kecamatan'), fn ($query) => $query->where('kecamatan_id', $request->kecamatan))
            ->when($request->filled('type'), fn ($query) => $query->where('school_type', $request->type))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status === 'aktif');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('education.schools.index', [
            'schools' => $schools,
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', School::class);

        return view('education.schools.create', [
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kelurahans' => collect(),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $this->authorize('create', School::class);

        $data = $request->validated();
        $subjects = $data['subjects'] ?? [];
        unset($data['subjects']);

        $school = School::create($data);
        $school->subjects()->sync($subjects);

        return redirect()
            ->route('education.schools.index')
            ->with('success', 'Data sekolah berhasil ditambahkan.');
    }

    public function show(School $school): View
    {
        $this->authorize('view', $school);

        $school->load(['kecamatan:id,name', 'kelurahan:id,name', 'subjects:id,name']);

        return view('education.schools.show', compact('school'));
    }

    public function edit(School $school): View
    {
        $this->authorize('update', $school);

        $school->load('subjects:id,name');

        return view('education.schools.edit', [
            'school' => $school,
            'kecamatans' => Kecamatan::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kelurahans' => Kelurahan::where('kecamatan_id', $school->kecamatan_id)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        $this->authorize('update', $school);

        $data = $request->validated();
        $subjects = $data['subjects'] ?? [];
        unset($data['subjects']);

        $school->update($data);
        $school->subjects()->sync($subjects);

        return redirect()
            ->route('education.schools.index')
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(School $school): RedirectResponse
    {
        $this->authorize('delete', $school);

        $school->delete();

        return redirect()
            ->route('education.schools.index')
            ->with('success', 'Data sekolah berhasil dihapus.');
    }
}
