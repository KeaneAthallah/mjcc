@props(['rows' => null])

@if ($rows && $rows->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <p class="text-[12px] text-gray-500">
            Menampilkan <strong>{{ $rows->firstItem() ?? 0 }}</strong>–<strong>{{ $rows->lastItem() ?? 0 }}</strong>
            dari <strong>{{ $rows->total() }}</strong> data
        </p>
        <div class="pagination-links">
            {{ $rows->links() }}
        </div>
    </div>
@endif
