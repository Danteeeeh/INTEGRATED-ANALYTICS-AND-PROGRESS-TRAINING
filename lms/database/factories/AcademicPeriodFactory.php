<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 years', '+1 year');
        $endDate = (clone $startDate)->modify('+6 months');

        return [
            'name' => fake()->randomElement(['Fall', 'Spring', 'Summer']).' '.fake()->year(),
            'code' => strtoupper(fake()->lexify('????')).fake()->year(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => fake()->boolean(20), // 20% chance of being current
            'is_enrollment_open' => fake()->boolean(50),
        ];
    }
}
