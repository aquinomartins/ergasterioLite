<div class="space-y-3">
    @forelse ($items as $item)
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div class="font-medium">{{ $item->display_name ?? $item->title }}</div>
            @if (isset($item->status))
                <div class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ $item->status }}</div>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-400">{{ $empty }}</p>
    @endforelse
</div>
