<nav role="navigation" aria-label="Navigasi halaman" class="ml-auto flex items-center gap-1">
    @if ($paginator->onFirstPage())
        <span class="flex h-8 items-center rounded-lg border border-line px-2.5 text-[12.5px] text-faint">Sebelumnya</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
           class="flex h-8 items-center rounded-lg border border-line px-2.5 text-[12.5px] font-medium text-muted no-underline hover:bg-slate-50 hover:text-ink">Sebelumnya</a>
    @endif

    @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 1), min($paginator->lastPage(), $paginator->currentPage() + 1)) as $page => $url)
        @if ($page === $paginator->currentPage())
            <span aria-current="page"
                  class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary px-2 text-[12.5px] font-semibold text-white">{{ $page }}</span>
        @else
            <a href="{{ $url }}"
               class="flex h-8 min-w-8 items-center justify-center rounded-lg border border-line px-2 text-[12.5px] font-medium text-muted no-underline hover:bg-slate-50 hover:text-ink">{{ $page }}</a>
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
           class="flex h-8 items-center rounded-lg border border-line px-2.5 text-[12.5px] font-medium text-muted no-underline hover:bg-slate-50 hover:text-ink">Berikutnya</a>
    @else
        <span class="flex h-8 items-center rounded-lg border border-line px-2.5 text-[12.5px] text-faint">Berikutnya</span>
    @endif
</nav>
