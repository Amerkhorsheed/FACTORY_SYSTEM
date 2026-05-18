@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('pagination.navigation') }}" class="pagination-nav">
        <div class="pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="pagination-mobile-link pagination-item-disabled" aria-disabled="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    {{ __('pagination.previous') }}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-mobile-link" aria-label="{{ __('pagination.previous') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    {{ __('pagination.previous') }}
                </a>
            @endif

            <span class="pagination-mobile-current">
                {{ __('pagination.page_x_of_y', ['current' => number_format($paginator->currentPage()), 'last' => number_format($paginator->lastPage())]) }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-mobile-link" aria-label="{{ __('pagination.next') }}">
                    {{ __('pagination.next') }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @else
                <span class="pagination-mobile-link pagination-item-disabled" aria-disabled="true">
                    {{ __('pagination.next') }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @endif
        </div>

        <div class="pagination-desktop">
            <div class="pagination-list">
                @if ($paginator->onFirstPage())
                    <span class="pagination-item pagination-item-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-item" aria-label="{{ __('pagination.previous') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="pagination-item pagination-item-disabled" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-item pagination-item-active" aria-current="page" aria-label="{{ __('pagination.current_page', ['page' => number_format($page)]) }}">
                                    {{ number_format($page) }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="pagination-item" aria-label="{{ __('pagination.go_to_page', ['page' => number_format($page)]) }}">
                                    {{ number_format($page) }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-item" aria-label="{{ __('pagination.next') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                @else
                    <span class="pagination-item pagination-item-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
