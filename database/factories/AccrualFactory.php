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
        $payees = ['a101', 'etk2'];

        // https://github.com/fzaninotto/Faker
        $accrual = [
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
            'payee' => $payees[$this->faker->biasedNumberBetween(0, 1)],
            // 'sent_at' => null,
            // 'opened_at' => null,
            // 'confirmed_at' => null,
            // 'paid_at' => null,
            // 'archived_at' => null,
            // 'comment' => '',
        ];

        $statuses = [
            'positive',
            'failed',
            'sent',
            'opened',
            'confirmed',
            'paid',
        ];

        $status = $this->faker->biasedNumberBetween(0, 5);
        switch ($status) {
            case 0:
                # positive sum
                $accrual['sum'] = -$accrual['sum'];
                $accrual['archived_at'] = $accrual['created_at'];
                break;

            case 1:
                # failed
                $accrual['archived_at'] = $this->faker->dateTimeBetween($accrual['created_at']);
                $accrual['comment'] = 'Ошибка отправки письма';
                break;

            case 2:
                # opened
                $accrual['sent_at'] = $this->faker->dateTimeBetween($accrual['created_at']);
                $accrual['opened_at'] = $this->faker->dateTimeBetween($accrual['sent_at']);
                break;

            case 3:
                # confirmed
                $accrual['sent_at'] = $this->faker->dateTimeBetween($accrual['created_at']);
                $accrual['opened_at'] = $this->faker->dateTimeBetween($accrual['sent_at']);
                $accrual['confirmed_at'] = $this->faker->dateTimeBetween($accrual['opened_at']);
                break;

            case 4:
                # paid
                $accrual['sent_at'] = $this->faker->dateTimeBetween($accrual['created_at']);
                $accrual['opened_at'] = $this->faker->dateTimeBetween($accrual['sent_at']);
                $accrual['confirmed_at'] = $this->faker->dateTimeBetween($accrual['opened_at']);
                $accrual['paid_at'] = $this->faker->dateTimeBetween($accrual['opened_at']);
                break;

            default:
                # sent
                $accrual['sent_at'] = $this->faker->dateTimeBetween($accrual['created_at']);
                break;
        }

        return $accrual;
    }
}
