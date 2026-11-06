<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\MessageTemplate;

class SaveMessageTemplate
{
    /**
     * Create or update a template. Whoever creates it is recorded as its author.
     *
     * @param  array{
     *     purpose: string,
     *     team_id?: int|null,
     *     name: string,
     *     body: string,
     *     is_active: bool,
     * }  $attributes
     */
    public function handle(MessageTemplate $template, array $attributes, User $editor): MessageTemplate
    {
        $template->fill($attributes);

        if (! $template->exists) {
            $template->author()->associate($editor);
        }

        $template->save();

        return $template;
    }
}
