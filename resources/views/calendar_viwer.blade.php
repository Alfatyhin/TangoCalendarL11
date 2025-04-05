<x-calendar-page>
    <x-slot name="header">
        <h1 class="font-semibold text-center text-xl text-gray-800 leading-tight">
            Tango Calendar..
        </h1>
    </x-slot>

    <div class="">
        <div class="">
            <div class="bg-white overflow-hidden shadow-xl">
                @livewire('calendars-list')
            </div>
        </div>
    </div>
</x-calendar-page>
