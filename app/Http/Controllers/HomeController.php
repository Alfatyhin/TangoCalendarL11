<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCalendarEvents;
use App\Models\EventsCalendarsMap;
use App\Models\Gcalendar;
use App\Models\GcalendarService;
use App\Services\CalendarDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{

    private $countries_list;
    private $countries_list_selected;
    private mixed $calendars_list;
    private mixed $calendars_selected = [3 => 3];
    private mixed $calendarsMap;
    private $currentDate;
    private $locale = 'uk';
    private \Illuminate\Support\Collection $weekDays;

    public function __construct()
    {

    }
    public function main(Request $request, $locale = false, $year = false, $month = false)
    {
        return view('calendar.dark', compact('locale', 'year', 'month'));

    }
    public function index()
    {
        return view('home');
    }
}
