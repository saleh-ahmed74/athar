@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pagination">
        <p class="pagination-summary">{{ __('Showing :first to :last of :total results', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem(), 'total' => $paginator->total()]) }}</p>
        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="pagination-button pagination-disabled" aria-disabled="true" aria-label="{{ __('Previous') }}">{{ __('Previous') }}</span>
            @else
                <button type="button" class="pagination-button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" aria-label="{{ __('Previous') }}">{{ __('Previous') }}</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-button pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-button pagination-current" aria-current="page">{{ $page }}</span>
                            @else
                                <button type="button" class="pagination-button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</button>
                            @endif
                        </span>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" class="pagination-button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}" aria-label="{{ __('Next') }}">{{ __('Next') }}</button>
            @else
                <span class="pagination-button pagination-disabled" aria-disabled="true" aria-label="{{ __('Next') }}">{{ __('Next') }}</span>
            @endif
        </div>
    </nav>
@endif