<?php

namespace App\Http\Requests\Management;

use App\Domain\Catalog\Models\Category;
use App\Domain\Organization\Models\Team;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    /**
     * A category may stay with its current team even if that team was disabled.
     */
    protected function teamRule(): Exists
    {
        return Rule::exists(Team::class, 'id')->where(fn (Builder $query) => $query
            ->where('is_active', true)
            ->orWhere('id', $this->category()->team_id));
    }

    protected function uniqueNameRule(): Unique
    {
        return parent::uniqueNameRule()->ignore($this->category());
    }

    private function category(): Category
    {
        return $this->route('category');
    }
}
