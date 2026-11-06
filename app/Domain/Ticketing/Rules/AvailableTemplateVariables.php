<?php

namespace App\Domain\Ticketing\Rules;

use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TemplateVariable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every {{variable}} in a template body must exist and have a value
 * when that kind of message is sent.
 */
class AvailableTemplateVariables implements ValidationRule
{
    public function __construct(private ?MessageTemplatePurpose $purpose) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        foreach (TemplateVariable::namesUsedIn($value) as $name) {
            $variable = TemplateVariable::tryFrom($name);
            $token = '{{'.$name.'}}';

            if ($variable === null) {
                $fail(__('The variable :variable does not exist.', ['variable' => $token]));

                continue;
            }

            if ($this->purpose !== null && ! $variable->isAvailableFor($this->purpose)) {
                $fail(__('The variable :variable cannot be used in this kind of message.', ['variable' => $token]));
            }
        }
    }
}
