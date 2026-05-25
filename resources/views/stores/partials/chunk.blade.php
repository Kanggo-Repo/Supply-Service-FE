<table>
    <tbody>
        @include('stores.partials.rows', ['stores' => $stores, 'pagination' => $pagination])
    </tbody>
</table>

@php
    $chunkBaseParams = array_filter([
        'search' => request('search'),
        'sort_by' => request('sort_by'),
        'sort_direction' => request('sort_direction'),
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="stores-chunk-state"
    data-base-url="{{ route('stores.chunk', $chunkBaseParams) }}"
    data-current-page="{{ (int) ($pagination['current_page'] ?? 1) }}"
    data-last-page="{{ (int) ($pagination['last_page'] ?? 1) }}"
    data-next-page="{{ $pagination['next_page'] ?? '' }}"
    hidden></div>
