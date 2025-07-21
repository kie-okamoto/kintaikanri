<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
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

            // 出退勤の前後関係チェック
            if ($clockIn && $clockOut && $clockIn > $clockOut) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);

            foreach ($breaks as $index => $break) {
                $start = $break['start'] ?? null;
                $end = $break['end'] ?? null;

                // 勤務時間外チェック
                if ($clockIn && $start && $start < $clockIn) {
                    $validator->errors()->add("breaks.$index.start", '休憩時間が勤務時間外です');
                }

                if ($clockOut && $end && $end > $clockOut) {
                    $validator->errors()->add("breaks.$index.end", '休憩時間が勤務時間外です');
                }
            }

            // 休憩時間の重複チェック
            for ($i = 0; $i < count($breaks); $i++) {
                $startA = $breaks[$i]['start'] ?? null;
                $endA = $breaks[$i]['end'] ?? null;

                if (!$startA || !$endA) continue;

                for ($j = $i + 1; $j < count($breaks); $j++) {
                    $startB = $breaks[$j]['start'] ?? null;
                    $endB = $breaks[$j]['end'] ?? null;

                    if (!$startB || !$endB) continue;

                    // 重複している場合
                    if ($startA < $endB && $startB < $endA) {
                        $validator->errors()->add("breaks.$j.start", '休憩時間が重複しています');
                    }
                }
            }
        });
    }
}
