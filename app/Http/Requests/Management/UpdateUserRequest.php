<?php

namespace App\Http\Requests\Management;

use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends StoreUserRequest
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->editedUser())],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * Nobody can change their own role or disable their own account,
     * so the platform never loses the person configuring it.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if (! $this->editedUser()->is($this->user())) {
                    return;
                }

                if ($this->integer('role_id') !== $this->editedUser()->role_id) {
                    $validator->errors()->add('role_id', __('You cannot change your own role.'));
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('You cannot disable your own account.'));
                }
            },
        ];
    }

    private function editedUser(): User
    {
        return $this->route('user');
    }
}
