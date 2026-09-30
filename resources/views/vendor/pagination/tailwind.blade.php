@if ($paginator->hasPages())
    <div class="pagination" style="padding: 10px 0; text-align: center;">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="disabled" style="padding: 2px 8px; margin: 0 2px; color: #999; border: 1px solid #ddd;">&laquo; 上一頁</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding: 2px 8px; margin: 0 2px; border: 1px solid #ccc; color: #0B55C4; text-decoration: none;">&laquo; 上一頁</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span style="padding: 2px 8px; margin: 0 2px; color: #999;">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="current" style="padding: 2px 8px; margin: 0 2px; border: 1px solid #0B55C4; background: #0B55C4; color: #fff; font-weight: bold;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding: 2px 8px; margin: 0 2px; border: 1px solid #ccc; color: #0B55C4; text-decoration: none;">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding: 2px 8px; margin: 0 2px; border: 1px solid #ccc; color: #0B55C4; text-decoration: none;">下一頁 &raquo;</a>
        @else
            <span class="disabled" style="padding: 2px 8px; margin: 0 2px; color: #999; border: 1px solid #ddd;">下一頁 &raquo;</span>
        @endif
    </div>
@endif
