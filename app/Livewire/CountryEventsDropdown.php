<?php

namespace App\Livewire;

use App\Models\Gcalendar;
use Livewire\Component;
use Illuminate\Support\Carbon;

class CountryEventsDropdown extends Component
{
    public bool $open = false;

    public string $search = '';

    public ?string $selectedCountrySlug = null;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function render()
    {
        $countries = Gcalendar::query()
            ->select('country')
            ->selectRaw('COUNT(*) as calendars_count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->where('country', '!=', 'All')
            ->when($this->search, function ($query) {
                $query->where('country', 'like', '%' . $this->search . '%');
            })
            ->groupBy('country')
            ->orderByDesc('calendars_count')
            ->orderBy('country')
            ->limit(12)
            ->get();

        return view('livewire.country-events-dropdown', [
            'countries' => $countries,
            'monthName' => now()->translatedFormat('F'),
        ]);
    }
}
