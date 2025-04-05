<div class="row">
        <div class="col select-floating">
            <strong class="pointer {{ $isFocused ? 'focused' : '' }}"
                    size="{{ $isFocused ? $size_focused : 1 }}"
                    wire:click="onFocusChecked"
                    @if($select_name)
                        name="{{ $select_name }}"
                    @endif
            > {{ $first_option }} </strong>
            <div>

                <ul class="list">
                    @foreach($selectedValues as $v)
                        @isset($selectArray[$v])
                            <li>
                                <div class="">
                                    {{ str_replace('_', ' ', $selectArray[$v]) }}
                                     <span
                                         wire:click="setLastSelect({{ $v }})"
                                         class="close">
                                         +
                                     </span>
                                </div>
                            </li>
                        @endisset
                    @endforeach
                </ul>
            </div>
                @if($isFocused)
                    <div class="float-select">
                        <div class="row h-1">
                            <span
                                wire:click="onFocusChecked"
                                class="close">+</span>
                        </div>
                        <h5>
                            {{ $first_option }}
                        </h5>
                        @if(!empty($search_title))
                            <div>
                                <input type="text" class="form-control form-control-sm"
                                       wire:model.live="searchFilter"
                                       wire:focus="onFocusSearch"
                                       {{--                       wire:blur="onBlurSearch"--}}
                                       placeholder="{{ $search_title }}">
                                <span
                                    wire:click="searchClear"
                                    class="close search_close">+</span>
                            </div>
                        @endif
                        <ul class="list">
                            @foreach($selectArray as $k => $item)
                                @if($option_value == 'key')
                                    <li
                                        wire:click="setLastSelect({{ $k }})"
                                        class="@if(in_array($k, $selectedValues)) selected @endif"
                                    >{{ str_replace('_', ' ', $item) }} </li>
                                @else
                                    <li
                                        wire:click="setLastSelect({{ $k }})"
                                        class="
                                    @if(in_array($item[$option_value], $selectedValues))
                                        selected
                                @endif
                                "
                                    > {{ $item[$option_name] }} </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

        </div>
</div>
