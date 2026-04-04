<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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

            'breaks' => ['array'],
            'breaks.*.break_start' => ['nullable'],
            'breaks.*.break_end' => ['nullable'],
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

            foreach ($breaks as $index => $break) {
                $breakStart = $break['break_start'] ?? null;
                $breakEnd = $break['break_end'] ?? null;

                if (!$breakStart && !$breakEnd) {
                    continue;
                }

                if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                    continue;
                }

                if ($breakStart < $clockIn || $breakStart > $clockOut) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }

                if ($breakEnd > $clockOut) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($breakStart >= $breakEnd) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }
            }

            if (!$this->filled('note')) {
                $validator->errors()->add('note', '備考を記入してください');
            }
        });
    }

    public function messages()
    {
        return [
            'clock_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
        ];
    }
}
