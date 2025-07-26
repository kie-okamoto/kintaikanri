<?php

namespace Database\Factories;

use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StampCorrectionRequestFactory extends Factory
{
    protected $model = StampCorrectionRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_date' => Carbon::today(),
            'reason' => $this->faker->sentence,
            'note' => $this->faker->realText(30),
            'requested_at' => now(),
            'status' => '承認待ち',
        ];
    }
}
