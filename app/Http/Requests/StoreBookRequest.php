<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'published_year' => ['required', 'integer', 'digits:4', 'min:1000', 'max:'.date('Y')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser un texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'title.min' => 'El título debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
            'published_year.digits' => 'El año de publicación debe tener 4 dígitos.',
            'published_year.min' => 'El año de publicación debe ser un año válido (mínimo :min).',
            'published_year.max' => 'El año de publicación no puede ser posterior a :max.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }

    /**
     * Nombres legibles de los atributos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'author' => 'autor',
            'description' => 'descripción',
            'published_year' => 'año de publicación',
            'category_id' => 'categoría',
        ];
    }
}
