@props(['paginator'])
@if($paginator->hasPages())
    <div class="mt-4 flex flex-col gap-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
        <p>{{ __('ui.labels.pagination', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
        <div>{{ $paginator->links() }}</div>
    </div>
@endif
