<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceUpdateRequest extends FormRequest
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
            'clock_in' => ['required'],
            'clock_out' => ['required'],
            'note' => ['required', 'string'],
            'breaks.*.break_start' => ['nullable'],
            'breaks.*.break_end' => ['nullable'],
        ];
    }

    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');
            $breaks = $this->input('breaks', []);

            if ($clockIn && $clockOut && $clockIn >= $clockOut) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ($breaks as $break) {
                $breakStart = $break['break_start'] ?? null;
                $breakEnd = $break['break_end'] ?? null;

                if ($breakStart) {
                    if (($clockIn && $breakStart < $clockIn) || ($clockOut && $breakStart > $clockOut)) {
                        $validator->errors()->add('breaks', '休憩時間が不適切な値です');
                    }
                }

                if ($breakEnd) {
                    if ($clockOut && $breakEnd > $clockOut) {
                        $validator->errors()->add('breaks', '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }

                if ($breakStart && $breakEnd && $breakStart >= $breakEnd) {
                    $validator->errors()->add('breaks', '休憩時間が不適切な値です');
                }
            }
        });
    }
}
