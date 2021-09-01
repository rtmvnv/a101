<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Accrual;

class MoneyMailRuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Колбек от Mailru корректно обрабатывается для существующего счета.
     *
     * @return void
     */
    public function test_callback_is_processed()
    {

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
         * Подготовить данные колбека Mailru
         */
        $action = '/api/mailru';
        $params = [];

        $paramsObject = (object)[];
        $paramsObject->body = (object)$params;
        $paramsObject->header = (object)[];
        $paramsObject->header->ts = time();
        $paramsObject->header->client_id = config('services.money_mail_ru.merchant_id');

        $paramsJson = json_encode($paramsObject);
        $data = base64_encode($paramsJson);

        $signatureString = $action . $data . config('services.money_mail_ru.key');
        $signature = sha1($signatureString);
        $response = $this->post(
            $action,
            [
                'version' => env('MONEYMAILRU_VERSION'),
                'data' => $data,
                'signature' => $signature
            ]
        );

        $response->assertStatus(200);

        // $this->assertDatabaseHas(
        //     'accruals',
        //     [
        //         'uuid' => $accrual->uuid,
        //         'completed_at' => ''
        //     ]
        // );
    }
}
