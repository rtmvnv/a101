<?php

namespace Tests\Feature;

use Tests\TestCase;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Faker\Generator as Faker;
use App;
use App\A101;
use App\Models\Accrual;
use App\UniOne\UniOne;

class MoneyMailRuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * При получении нового счета старый неоплаченный счет
     * на данный аккаунт получает статус failed
     *
     * @return void
     */
    public function testOldAccountIsSetFailed()
    {
        $this->withoutExceptionHandling();

        /**
         * Первое начисление за предыдущий месяц в статусе "отправлено"
         */
        $accrualNotCompleted = Accrual::factory()->create([
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'sent_at' => now(),
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        /**
         * Второе начисление за текущий месяц в статусе "оплачено"
         */
        $accrualCompleted = Accrual::factory()->create([
            'account' => $accrualNotCompleted->account,
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('this month')),
            'sent_at' => now(),
            'opened_at' => now(),
            'confirmed_at' => now(),
            'paid_at' => now(),
            'archived_at' => now(),
        ]);

        /**
         * Третье начисление за следующий месяц в статусе "создано"
         */
        $accrualNew = Accrual::factory()->create([
            'account' => $accrualNotCompleted->account,
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('next month')),
            'sent_at' => now(),
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        $a101 = new A101();
        $a101->cancelOtherAccruals($accrualNew);

        // Первое начисление отмечено archived
        $this->assertDatabaseMissing(
            'accruals',
            [
                'period' => $accrualNotCompleted->period,
                'archived_at' => null,
            ]
        );

        // Второе начисление отмечено archived
        $this->assertDatabaseMissing(
            'accruals',
            [
                'period' => $accrualCompleted->period,
                'archived_at' => null,
            ]
        );

        // Третье начисление не отмечено archived
        $this->assertDatabaseHas(
            'accruals',
            [
                'period' => $accrualNew->period,
                'archived_at' => null,
            ]
        );
    }

    /**
     * Колбек от Mailru корректно обрабатывается для существующего счета.
     *
     * @return void
     */
    public function testFullCycle()
    {
        $this->withoutExceptionHandling();

        /*
         * Mock email sender not to send real messages
         */
        $mock = $this->mock(UniOne::class, function (MockInterface $mock) {
            $mock->shouldReceive('emailSend')
            ->andReturn([
                'status' => 'success',
                'job_id' => '101',
            ], [
                'status' => 'success',
                'job_id' => '102',
            ]);
        });
        app()->instance(UniOne::class, $mock);

        /*
         * Create an accrual
         */
        $faker = app(Faker::class);
        $data = [
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'account' => 'БВ' . $faker->randomNumber(6, true),
            'name' => $faker->name(),
            'email' => 'null@vic-insurance.ru',
        ];

        $a101 = app(A101::class);
        $data['signature'] = $a101->postApiAccrualsSignature($data);

        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');

        // Интерфейс API доступен
        $response->assertStatus(200);

        /**
         * Клиент переходит по ссылке
         */
        $accrual = Accrual::first();
        $response = $this->get('/accrual/' . $accrual->uuid);
        $response->assertStatus(200);
        $accrual->refresh();

        /**
         * Клиент получает ссылку на оплату
         */
        $transaction_id = (string) Str::uuid();
        $accrual->transaction_id = $transaction_id;
        $accrual->confirmed_at = now();
        $accrual->save();

        /**
         * Подготовить данные колбека Mailru
         */
        $data = base64_encode(json_encode([
            'body' => [
                'notify_type' => 'TRANSACTION_STATUS',
                'issuer_id' => $accrual->uuid,
                'transaction_id' => $transaction_id,
                'status' => 'PAID',
            ],
            'header' => [
                'status' => 'OK',
                'ts' => (string)time(),
                'client_id' => config('services.money_mail_ru.merchant_id'),
            ]
        ]));

        // Не проверять signature, так как мы не можем ее сгенерировать вместо Mail.ru
        Config::set("services.money_mail_ru.verify_signature", false);

        // Отправляется письмо подтверждения оплаты
        $response = $this->post(
            '/api/mailru',
            [
                'version' => config('services.money_mail_ru.version'),
                'data' => $data,
                'signature' => 'dummysignature',
            ]
        );

        // Интерфейс API доступен
        $response->assertStatus(200);

        // Информация о платеже есть в базе
        $this->assertDatabaseHas('accruals', ['transaction_id' => $transaction_id]);

        $accrual = Accrual::where('transaction_id', $transaction_id)->first();
        $this->assertNotEmpty($accrual);

        $this->assertEquals('paid', $accrual->status);
    }
}
