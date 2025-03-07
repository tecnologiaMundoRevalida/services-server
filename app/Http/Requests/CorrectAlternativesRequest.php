<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CorrectAlternativesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'discursive_answers' => 'required|array|min:1',
            'discursive_answers.*.question_text' => 'required|string',
            'discursive_answers.*.alternatives' => 'required|array|min:1',
            'discursive_answers.*.alternatives.*.alternative_text' => 'required|string',
            'discursive_answers.*.alternatives.*.student_answer' => 'required|string',
            'discursive_answers.*.alternatives.*.correct_answer' => 'required|string',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erro de validação',
            'errors' => $validator->errors(),
        ], 422));
    }
}
