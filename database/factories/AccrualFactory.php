<?php

namespace Database\Factories;

use App\Models\Accrual;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccrualFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Accrual::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // https://github.com/fzaninotto/Faker
        return [
            'uuid' => (string) Str::uuid(),
            'sum' => $this->faker->randomFloat(2, 100, 1000),
            'period' => date('Ym'),
            'account' => 'БВ' . $this->faker->randomNumber(6, true),
            'email' => 'null@vic-insurance.ru',
            'name' => $this->faker->name(),
            // 'date_a101' => null,
            'url_bank' => '',
            'created_at' => $this->faker->dateTimeBetween('-1 week'),
            'updated_at' => now(),
            // 'sent_at' => null,
            // 'opened_at' => null,
            // 'confirmed_at' => null,
            // 'paid_at' => null,
            // 'archived_at' => null,
            // 'comment' => '',
        ];
    }
}
