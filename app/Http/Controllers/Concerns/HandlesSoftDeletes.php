<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Adds a trash index plus restore / force-delete actions for soft-deleting
 * resources. Controllers must set a `$softDeleteResource` array describing the
 * model class, route name, label and searchable column:
 *
 *     protected array $softDeleteResource = [
 *         'class' => School::class,
 *         'route' => 'education.schools',
 *         'label' => 'sekolah',
 *         'searchColumn' => 'name',
 *     ];
 */
trait HandlesSoftDeletes
{
    /**
     * @return array{class: class-string, route: string, label: string, searchColumn: string}
     */
    protected function softDeleteConfig(): array
    {
        return $this->softDeleteResource;
    }

    public function trash(): View
    {
        $config = $this->softDeleteConfig();
        $model = $config['class'];

        $this->authorize('viewAny', $model);

        $trashed = $model::onlyTrashed()
            ->when(request()->filled('search'), function ($query) use ($config) {
                $query->where($config['searchColumn'], 'like', '%'.request('search').'%');
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('trash.index', [
            'trashed' => $trashed,
            'route' => $config['route'],
            'label' => $config['label'],
            'searchColumn' => $config['searchColumn'],
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $config = $this->softDeleteConfig();
        $model = $config['class'];

        $record = $model::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $record);

        $record->restore();

        return redirect()
            ->route($config['route'].'.index')
            ->with('success', 'Data '.$config['label'].' berhasil dipulihkan.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $config = $this->softDeleteConfig();
        $model = $config['class'];

        $record = $model::onlyTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $record);

        $record->forceDelete();

        return redirect()
            ->route($config['route'].'.trash')
            ->with('success', 'Data '.$config['label'].' dihapus permanen.');
    }
}
