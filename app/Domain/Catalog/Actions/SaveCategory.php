<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Category;

class SaveCategory
{
    /**
     * Create or update a category.
     *
     * @param  array{
     *     team_id: int,
     *     sla_policy_id?: int|null,
     *     name: string,
     *     description?: string|null,
     *     icon: string,
     *     default_ticket_type: string,
     *     is_visible: bool,
     *     sort_order: int,
     * }  $attributes
     */
    public function handle(Category $category, array $attributes): Category
    {
        $category->fill($attributes)->save();

        return $category;
    }
}
