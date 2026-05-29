<?php

namespace App\Jobs;

use App\Services\CalendarDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessCalendarEvents implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Carbon $date, public string $calendar_id)
    {

    }

    public function uniqueId(): string
    {
        return $this->calendar_id . '-' . $this->date->format('Y-m');
    }

    public function handle(): void
    {
        $calendarDataService = new CalendarDataService();
        $months = [
            $this->date->copy()->subMonthNoOverflow(),
            $this->date->copy(),
            $this->date->copy()->addMonthNoOverflow(),
        ];

        foreach ($months as $month) {
            $data = [
                'month' => $month->startOfMonth()->format('Y-m-d'),
            ];

            $calendarDataService->getCalendarEventsNew($data, $this->calendar_id);
        }
    }
}
