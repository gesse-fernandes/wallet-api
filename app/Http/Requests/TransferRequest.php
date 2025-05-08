<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'payee_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'O campo amount é obrigatório.',
            'amount.numeric' => 'O campo amount deve ser um número.',
            'amount.min' => 'O valor mínimo para transferência é 1.',

            'payee_id.required' => 'O campo payee_id é obrigatório.',
            'payee_id.exists' => 'O destinatário informado não existe.',
        ];
    }
}
