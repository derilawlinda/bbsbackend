<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required','max:255'],
            'email' => ['required','max:255','email','unique:users'],
            'role_id'=> ['required'],
            'password'=> ['required','max:255'],
            'password_confirmed' => ['required','same:password']
        ];
    }

    
}
