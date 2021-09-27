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
            'sum' => mt_rand(1, 9999),
            'uuid' => (string) Str::uuid(),
            'period' => date('Ym'),
            'person' => (string) Str::uuid(),
            'full_name' => $this->faker->name(),
            'email' => 'null@vic-insurance.ru',
            // 'email' => preg_replace('/@example\..*/', '@vic-insurance.ru', $this->faker->safeEmail()),
            'account' => (string) Str::uuid(),
            'account_name' => 'БВ' . $this->faker->randomNumber(6, true),
            'sum' => $this->faker->randomFloat(2, 100, 1000),
            'sum_accrual' => $this->faker->randomFloat(2, 100, 1000),
            'sum_advance' => $this->faker->randomFloat(2, 100, 1000),
            'sum_debt' => $this->faker->randomFloat(2, -100, -1000),
            'org' => (string) Str::uuid(),
            'org_name' => 'А101-КОМФОРТ ООО',
            'org_account' => '40702810438000083214',
            // 'date_a101' => null,
            'url_bank' => '',
            'created_at' => $this->faker->dateTimeBetween('-1 week'),
            'updated_at' => now(),
            // 'sent_at' => null,
            // 'opened_at' => null,
            // 'confirmed_at' => null,
            // 'payed_at' => null,
            // 'archived_at' => null,
            // 'comment' => '',
        ];
    }
}
