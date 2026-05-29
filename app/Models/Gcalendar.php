<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Gcalendar extends Model
{
    use HasFactory;

    public $select;
    public $class;

    protected $casts = [
        'google_info_synced_at' => 'datetime',
        'google_info_sync_failed_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'data' => 'array',
    ];

    public function scopeTangoSchools(Builder $query): Builder
    {
        return $query->where('type_events', 'tango_school');
    }

    public function ensureSlug(): string
    {
        if (! empty($this->slug)) {
            return $this->slug;
        }

        $this->slug = $this->makeUniqueSlug($this->generateSlugFromName());

        return $this->slug;
    }

    public function generateSlugFromName(?string $name = null): string
    {
        $name = $this->cleanNameForSlug($name ?? $this->name ?? $this->gcalendarId ?? '');
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : 'calendar-' . $this->getKey();
    }

    private function cleanNameForSlug(string $name): string
    {
        return Str::of($name)
            ->replaceMatches('~https?://\S+|www\.\S+~i', ' ')
            ->replaceMatches('~[\w.+-]+@[\w.-]+\.\w+~', ' ')
            ->replaceMatches('~[^\pL\pN]+~u', ' ')
            ->squish()
            ->toString();
    }

    private function makeUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @param mixed $select
     */
    public function setSelect($select)
    {
        $this->select = $select;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }


}
