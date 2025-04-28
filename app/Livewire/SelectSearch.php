<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Component;

class SelectSearch extends Component
{
    public array $selectArray = [];
    public array $selectArrayOrigin = [];
    public $selectedValues = [];
    public $selectedValuesOld = [];
    public $searchFilter = '';
    public $option_value;
    public $option_name;
    public $select_name = '';
    public $search_title = '';
    public $first_option = 'Select Options';
    public $multiple = false;
    public $isFocused = false;
    public $size_focused = 3;
    public $isFilter = false;
    public $updCount = 0;
    public $lastSelect = '';
    public $lastSelectData = [];
    /**
     * @var array|mixed
     */
    public mixed $selectNew = '';
    /**
     * @var array|int|mixed|string|null
     */
    public mixed $selectNewTest = 'TEST - ';

    public function mount($option_value, $option_name)
    {
        $this->selectArrayOrigin = $this->selectArray;
        $this->option_value = $option_value;
        $this->option_name = $option_name;
        $this->selectedValuesOld = $this->selectedValues;
    }

    protected $listeners = ['syncSelectedValues','syncSearchFilter']; // Слушатель событий

    public function updatedSearchFilter()
    {

        if (strlen($this->searchFilter) >= 1) {
            $this->selectArray = [];
            $search = $this->searchFilter;
            foreach ($this->selectArrayOrigin as $key => $value) {
                if($this->option_value == 'key') {
                    if (
                        preg_match("/^$search/i", $value)
                        || in_array($key, $this->selectedValues)
                    ) {
                        $this->selectArray[$key] = $value;
                    }
                } else {
                    if (
                        preg_match("/^$search/i", $value[$this->option_name])
                        || in_array($value[$this->option_value], $this->selectedValues)
                    ) {
                        $this->selectArray[] = $value;
                    }
                }

            }
            $this->size_focused = sizeof($this->selectArray);
        }

    }

    public function setLastSelect($clickValue)
    {
        if ($clickValue != '') {

            if (!in_array($clickValue, $this->selectedValuesOld)) {
                $this->selectedValues[] = $clickValue;
//                $this->selectedValues = array_merge($this->selectedValuesOld , $this->selectedValues);
            } else {

                $selectedValues = [];
                foreach ($this->selectedValues as $value) {
                    if ($value != $clickValue) {
                        $this->selectNewTest .= '|' . $value;
                        $selectedValues[] = $value;
                    }
                }
                $this->selectedValues = $selectedValues;

            }

            $this->selectedValuesOld = $this->selectedValues;
            $this->emitValues();
        }
    }


    public function emitValues()
    {
        $this->dispatch('searchSelected', $this->selectedValues, $this->select_name);
    }

    public function onFocusChecked()
    {
        $this->isFocused = $this->isFocused ? false : true;
        if ($this->isFocused === false) {
            $this->selectArray = $this->selectArrayOrigin;
            $this->searchFilter = '';
        }
    }

    public function searchClear()
    {
        $this->selectArray = $this->selectArrayOrigin;
        $this->searchFilter = '';
    }

    public function onFocusSearch()
    {
        $this->isFocused = true;
        $this->isFilter = true;
        $this->updatedSearchFilter();
    }

    public function render()
    {
        $this->updCount = $this->updCount + 1;
        return view('livewire.select-search', [
            'selectArray' => $this->selectArray,
            'selectedValues' => $this->selectedValues,
        ]);
    }
}
