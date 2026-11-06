<?php

namespace App\Domain\Shared\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * For models where exactly one row is the default, such as the default
 * business calendar or SLA policy.
 *
 * @mixin Model
 */
trait HasSingleDefault
{
    /**
     * Remove the default flag from every other row when this one is the default.
     */
    public function demoteOtherDefaults(): void
    {
        if (! $this->getAttribute('is_default')) {
            return;
        }

        static::query()->whereKeyNot($this->getKey())->update(['is_default' => false]);
    }

    /**
     * The row used when nothing else was chosen.
     */
    public static function findDefault(): ?static
    {
        return static::query()->where('is_default', true)->first();
    }
}
