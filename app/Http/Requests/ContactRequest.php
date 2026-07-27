<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', (new Phone)],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
