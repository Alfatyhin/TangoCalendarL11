<a class="active" href="#">
    <span>▣</span> {{ __('site.calendar') }}
</a>

@foreach($eventsMap['category_map'] as $category => $categoryData)
    @php
        $count = $categoryData['All']['count'] ?? $categoryData['count'] ?? 0;
        $isActive = in_array($category, $activeTypes, true);
    @endphp

    @if($count > 0)
        <div>
            <a
                href="{{ $this->buildUrl(['type' => $this->toggleUrlValue($activeTypes, $category), ]) }}"
                wire:click.prevent="toggleType('{{ $category }}')"
                @class(['active' => $isActive])>
                <span>{{ $isActive ? '▣' : '□' }}</span>
                {{ __('categories.' . $category) }} - {{ $count }}
            </a>

            @if($isActive)
                @if($category === 'festivals')
                    <div class="sub_menu">
                        <div class="school-calendar-line">
                                    <span class="calendar hash_event
                                    {{ isset($calendarsSelected[$categoryData['All']['calendar']]) ? 'active' : '' }}"
                                         wire:click="toggleCalendar('{{ $categoryData['All']['calendar'] }}')"
                                    >
                                        <span class="calendar-name">
                                            All
                                        </span>

                                    <span class="calendar-count">•{{ $categoryData['All']['count'] ?? 0 }}</span>
                                    </span>
                        </div>
                        @foreach($categoryData as $country => $data)
                            @if($country != 'All' && $country != 'count')
                                <div class="school-calendar-line">
                                    <span class="calendar hash_event {{ $data['calendar'] }}
                                    {{ isset($calendarsSelected[$data['calendar']]) ? 'active' : '' }}"
                                          wire:click="toggleCalendar('{{ $data['calendar'] }}')"
                                    >
                                        <span class="calendar-name">
                                            {{ $country }}
                                        </span>

                                    <span class="calendar-count">•{{ $data['count'] ?? 0 }}</span>
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(in_array($category, ['tango_school', 'practices', 'milongas'], true))
                    <div class="sub_menu">
                        @foreach($categoryData as $country => $data)
                            @if($country !== 'count')
                                @foreach($data as $city => $cityData)
                                    @if($city !== 'count' && ($cityData['count'] ?? 0) > 0)
                                        @php
                                            $isCityActive = in_array($city, $activeCities, true);
                                        @endphp

                                        <a
                                            href="{{ $this->buildUrl(['city' => $this->toggleUrlValue($activeCities, $city), ]) }}"
                                            wire:click.prevent="toggleCity('{{ $city }}')"
                                            @class(['active' => $isCityActive])
                                        >
                                            <span>{{ $isCityActive ? '▣' : '□' }}</span>
                                            {{ $city }} - {{ $cityData['count'] }}
                                        </a>

                                        @if($category == 'tango_school' && $isCityActive)
                                            <div class="sub_menu">
                                                @foreach($cityData['calendars'] as $cid => $cal_count)
                                                   @if($cal_count > 0)
                                                        <div class="school-calendar-line">
                                                            <span class="calendar hash_event {{ isset($calendars_filter[$cid]) ? '' : 'active' }}"
                                                                  wire:click="toggleCalendar('{{ $cid }}')">
                                                                <span class="calendar-name">
                                                                    {{ $eventsMap['calendars'][$cid]['name'] }}
                                                                </span>

                                                            </span>
                                                            <span class="calendar-count">•{{ $cal_count }}</span>
                                                            <a class="calendar-link" href="" title="calendar school">🗓</a>
                                                        </div>
                                                   @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif

                                @endforeach
                            @endif

                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    @else
        {{ __('categories.' . $category) }} - {{ $count }} <br>
    @endif
@endforeach
