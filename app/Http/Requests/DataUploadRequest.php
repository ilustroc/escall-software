<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataUploadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'archivo' => ['required','file','mimes:xlsx','max:51200'], // 50MB
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.mimes' => 'El archivo debe ser XLSX.',
            'archivo.max'   => 'El archivo es demasiado grande para la configuración actual.',
        ];
    }
}
