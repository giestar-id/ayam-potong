@props(['label', 'value', 'note' => '', 'icon' => 'wallet', 'featured' => false])
<article {{ $attributes->class(['panel stat-card min-w-0 overflow-hidden']) }}>
    <div class="flex min-h-8 items-center justify-between gap-2">
        <h2 class="stat-label truncate" title="{{ $label }}">{{ $label }}</h2>
        <span class="stat-icon"><x-icon :name="$icon"/></span>
    </div>
    <p class="stat-value" title="{{ $value }}">{{ $value }}</p>
    @if($note)<p class="metric-note truncate" title="{{ $note }}">{{ $note }}</p>@endif
</article>
