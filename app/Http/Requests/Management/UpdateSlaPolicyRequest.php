<?php

namespace App\Http\Requests\Management;

use App\Domain\ServiceLevel\Models\SlaPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSlaPolicyRequest extends StoreSlaPolicyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['required', 'string', 'max:100', Rule::unique(SlaPolicy::class)->ignore($this->policy())],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * The default policy stops being the default only when another one takes its place.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->policy()->is_default && ! $this->boolean('is_default')) {
                    $validator->errors()->add('is_default', __('There must always be a default policy. Mark another one as default instead.'));
                }
            },
        ];
    }

    private function policy(): SlaPolicy
    {
        return $this->route('slaPolicy');
    }
}
