@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Sahifalar') }}" class="mt-6 flex items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span class="border rounded px-3 py-2 text-sm text-gray-400 cursor-default">&larr; {{ __('Oldingi') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="border rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">&larr; {{ __('Oldingi') }}</a>
        @endif

        <span class="sm:hidden text-sm text-gray-600">
            {{ __('Sahifa :current / :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}
        </span>

        <div class="hidden sm:flex flex-wrap justify-center gap-1">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 py-2 text-sm text-gray-500 cursor-default">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="bg-gray-800 text-white rounded px-3 py-2 text-sm cursor-default">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="border rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="border rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('Keyingi') }} &rarr;</a>
        @else
            <span class="border rounded px-3 py-2 text-sm text-gray-400 cursor-default">{{ __('Keyingi') }} &rarr;</span>
        @endif
    </nav>
@endif
