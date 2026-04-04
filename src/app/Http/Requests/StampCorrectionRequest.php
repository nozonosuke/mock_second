<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StampCorrectionRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'attendance_id' => 'required|exists:attendances,id',
            'note' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'attendance_id.required' => '勤怠情報が取得できませんでした。',
            'attendance_id.exists' => '存在しない勤怠情報です。',
            'reason.required' => '修正理由を入力してください。',
            'note.string' => '修正理由は文字列で入力してください。',
            'note.max' => '修正理由は255文字以内で入力してください。',
        ];
    }
}
