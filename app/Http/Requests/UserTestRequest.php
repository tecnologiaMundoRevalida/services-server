<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UserTestRequest extends FormRequest
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
            "id" => "nullable",
            "name" => "required",
            "description" => "nullable",
            // "data" => "required|json",
            "qtd_questions" => "sometimes|integer",
        
            'areas' => ["array","sometimes"],
            'is_comment' => ["integer","sometimes"],
            'id_discursive' => ["integer","sometimes"],
            'specialties' => ["array","sometimes"],
            'tests' => ["array","sometimes"],
            'themes' => ["array","sometimes"],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'data'      => $validator->errors()

        ],422)); 
    }

    public function messages()
    {
        return [
            "name.required" => "O campo nome é obrigatório",
            "qtd_questions.integer" => "O campo qtd_questions deve ser um número inteiro",
            "areas.array" => "O campo areas deve ser um array",
            "is_comment.integer" => "O campo is_comment deve ser um número inteiro",
            "id_discursive.integer" => "O campo id_discursive deve ser um número inteiro",
            "specialties.array" => "O campo specialties deve ser um array",
            "tests.array" => "O campo tests deve ser um array",
            "themes.array" => "O campo themes deve ser um array",
        ];
    }
}
