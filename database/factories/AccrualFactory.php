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

        // $table->id();
        // $table->uuid('uuid')->unique();
        // $table->string('period', 6)->comment('Период начислений. "202105" - май 2021 года');
        // $table->string('person')->comment('"ФизическоеЛицо"');
        // $table->string('full_name')->comment('"ФИО"');
        // $table->string('email')->comment('"Email"');
        // $table->string('account')->comment('"ЛицевойСчет" - уникальный идентификатор лицевого счета');
        // $table->string('account_name')->comment('"ЛС" - наименование лицевого счета');
        // $table->decimal('sum', 10, 2)->comment('"СуммаОплаты"');
        // $table->decimal('sum_accrual', 15, 2)->comment('"СуммаНачисления"');
        // $table->decimal('sum_advance', 15, 2)->comment('"Аванс"');
        // $table->decimal('sum_debt', 15, 2)->comment('"СуммаДолга"');
        // $table->string('org')->comment('"Организация"');
        // $table->string('org_name')->comment('"ОрганизацияНаименование"');
        // $table->string('org_account')->comment('"НаименованиеБанковскогоСчетаУК"');
        // $table->date('date_a101')->nullable()->comment('"ДатаВыгрузки"');
        // $table->text('url_bank')->nullable()->comment('Ссылка для оплаты на страницу банка');
        // $table->timestamps();
        // $table->timestamp('sent_at')->nullable()->comment('Время отправки письма');
        // $table->timestamp('opened_at')->nullable()->comment('Время открытия письма клиентом');
        // $table->timestamp('confirmed_at')->nullable()->comment('Время нажатия клиентом ссылки "Оплатить"');
        // $table->timestamp('completed_at')->nullable()->comment('Время проведения платежа');
        // $table->timestamp('failed_at')->nullable()->comment('Время когда произошла ошибка');
        // $table->string('failed_comment')->nullable()->comment('Пояснение произошедшей ошибки');

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
            // 'completed_at' => null,
            // 'failed_at' => null,
            // 'failed_comment' => '',
            // 'completed_at' => null,
        ];
    }
}
