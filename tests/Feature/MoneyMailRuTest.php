<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Models\Accrual;
use App\MoneyMailRu\Callback;

class MoneyMailRuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Колбек от Mailru корректно обрабатывается для существующего счета.
     *
     * @return void
     */
    public function testCallbackIsProcessed()
    {
        $this->withoutExceptionHandling();

        /**
         * Создать счет и отметить отправленным
         */
        $accrual = Accrual::factory()->create();
        $accrual->sent_at = now();
        $accrual->save();

        /**
         * Клиент переходит по ссылке
         */
        $response = $this->get('/' . $accrual->uuid);
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
        $this->assertDatabaseHas('accruals', ['transaction_id' => $transaction_id ]);

        $accrual = Accrual::where('transaction_id', $transaction_id)->first();
        $this->assertNotEmpty($accrual);

        $this->assertEquals('completed', $accrual->status);
    }
}
