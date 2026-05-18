@props(['paginator'])
@if($paginator->hasPages())
    @php
        $from = $paginator->firstItem() ?? 0;
        $to = $paginator->lastItem() ?? 0;
    @endphp
    <div class="pagination-shell">
        <p class="pagination-summary">
            {{ __('ui.labels.pagination', [
                'from' => number_format($from),
                'to' => number_format($to),
                'total' => number_format($paginator->total()),
            ]) }}
        </p>
        <div class="pagination-controls">{{ $paginator->links() }}</div>
    </div>
@endif
