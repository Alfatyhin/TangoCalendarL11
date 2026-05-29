<div class="country-filter  {{ $open ? 'open' : '' }}" x-data @click.outside="$wire.close()">
    <button type="button" class="country-filter__button" wire:click="toggle">
        <span class="country-filter__button-icon">🌍</span>
        <span>All countries</span>
        <span class="country-filter__button-arrow"></span>
    </button>

    @if($open)
        <div class="country-filter__dropdown">
            <div class="country-filter__search">
                <span>⌕</span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Country search..."
                >
            </div>

            <div class="country-filter__title">
                Countries
            </div>

            <div class="country-filter__list">
                @forelse($countries as $country)
                    <a

                        href="{{ route('main', ['locale' => App::getLocale(), 'country' => $country->country]) }}"
                        class="country-filter__item"
                    >
                        <div class="country-filter__country">
                            @if($country->flag_url)
                                <img src="{{ $country->flag_url }}" alt="{{ $country->country }}" class="country-filter__flag">
                            @else
{{--                                <span class="country-filter__flag-placeholder"></span>--}}
                            @endif

                            <span class="country-filter__name">
                                {{ $country->country }}
                            </span>
                        </div>

                        <div class="country-filter__meta">
                            <span class="country-filter__count">
                                {{ $country->calendars_count }} tango calendars
                            </span>

{{--                            @if($country->nearest_event_date)--}}
{{--                                <span class="country-filter__nearest">--}}
{{--                                    ближайший:--}}
{{--                                    <strong>--}}
{{--                                        {{ \Illuminate\Support\Carbon::parse($country->nearest_event_date)->translatedFormat('j F') }}--}}
{{--                                    </strong>--}}
{{--                                </span>--}}
{{--                            @endif--}}
                        </div>

                        <span class="country-filter__chevron">›</span>
                    </a>
                @empty
                    <div class="country-filter__empty">
                        Countries events not found
                    </div>
                @endforelse
            </div>

            <a href="{{ route('main', ['locale' => App::getLocale()]) }}" class="country-filter__all">
                <span>◎</span>
                <span>All countries</span>
                <span>›</span>
            </a>
        </div>
    @endif
</div>
