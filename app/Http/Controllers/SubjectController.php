<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Subject::class);

        $subjects = Subject::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status === 'aktif');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        $this->authorize('create', Subject::class);

        return view('master.subjects.create');
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Subject::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Subject::create($data);

        return redirect()
            ->route('master.subjects.index')
            ->with('success', 'Data mata pelajaran berhasil ditambahkan.');
    }

    public function show(Subject $subject): View
    {
        $this->authorize('view', $subject);

        return view('master.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject): View
    {
        $this->authorize('update', $subject);

        return view('master.subjects.edit', compact('subject'));
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $subject->update($data);

        return redirect()
            ->route('master.subjects.index')
            ->with('success', 'Data mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        $subject->delete();

        return redirect()
            ->route('master.subjects.index')
            ->with('success', 'Data mata pelajaran berhasil dihapus.');
    }
}
