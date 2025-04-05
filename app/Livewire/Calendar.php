<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;

class Calendar extends Component
{


    public $eventsMap = [];

    public function mount($eventsMap)
    {
        $this->currentDate = Carbon::now()->startOfMonth();
        $this->getDates();
    }

    public function previousMonth()
    {
        $this->currentDate = $this->currentDate->subMonth()->startOfMonth();
        $this->getDates();
    }

    public function nextMonth()
    {
        $this->currentDate = $this->currentDate->addMonth()->startOfMonth();
        $this->getDates();
    }

    public function getDates()
    {
        $daysInMonth = $this->currentDate->daysInMonth;
        $firstDayOfMonth = $this->currentDate->copy()->startOfMonth()->dayOfWeek;
        $lastDayOfMonth = $this->currentDate->copy()->endOfMonth()->dayOfWeek;

        $weeks = [];
        $week = [];

        // Добавляем предыдущий месяц
        $prevMonth = $this->currentDate->copy()->subMonth();
        $prevDays = $firstDayOfMonth === 0 ? 6 : $firstDayOfMonth - 1;
        for ($i = $prevDays; $i > 0; $i--) {
            $week[] = [
                'date' => $prevMonth->copy()->endOfMonth()->subDays($i - 1),
                'current' => false
            ];
        }

        // Текущий месяц
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $week[] = [
                'date' => $this->currentDate->copy()->day($i),
                'current' => true
            ];
            if (count($week) == 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        // Добавляем следующий месяц
        $nextMonth = $this->currentDate->copy()->addMonth();
        if ($lastDayOfMonth != 0) {
            $nextDays = $lastDayOfMonth === 7 ? 0 : 7 - $lastDayOfMonth;
            for ($i = 1; $i <= $nextDays; $i++) {
                $week[] = [
                    'date' => $nextMonth->copy()->day($i),
                    'current' => false
                ];
                if (count($week) == 7) {
                    $weeks[] = $week;
                    $week = [];
                }
            }
        }

        if (!empty($week)) {
            $weeks[] = $week;
        }

        $this->startDate = $weeks[0][0]['date'];
        $this->endDate = $weeks[sizeof($weeks) - 1][6]['date'];

        $this->weeks = $weeks;

        $this->emitValues();
    }


    public function emitValues()
    {
        $this->dispatch('calendarListen', $this->startDate, $this->endDate, $this->currentDate);
    }

    public function render()
    {

        return view('livewire.calendar', [
            'eventsMap' => $this->eventsMap,
            'weeks' => $this->weeks,
            'monthYear' => $this->currentDate->format('F Y')
        ]);
    }
}
