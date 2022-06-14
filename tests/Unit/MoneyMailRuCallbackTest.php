<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\MoneyMailRu\Exception;
use App\MoneyMailRu\Callback;
use Illuminate\Http\Request;

class MoneyMailRuCallbackTest extends TestCase
{
    /**
     * Проверяет, что успешно обрабатывается входящий запрос
     * и создается объект MoneyMailRu\Callback
     *
     * @return void
     */
    public function testCallbackIsCreated()
    {
        $request = new Request();
        $request->merge([
            'data' => base64_encode(json_encode([
                'body' => [
                    'client_id' => config('services.money_mail_ru.merchant_id'),
                    'transaction_id' => '123',
                    'notify_type' => 'TRANSACTION_STATUS'
                ],
                'header' => 'test'
            ])),
            'signature' => 'dummysignature'
        ]);
        $callback = new Callback($request);
        $callback->validationRequired = false;
        $this->assertInstanceOf("App\MoneyMailRu\Callback", $callback);

        $this->assertEquals(
            config('services.money_mail_ru.merchant_id'),
            $callback->body['client_id']
        );
    }

    /**
     * Проверка функции Callback::respondFatal()
     */
    public function testErrorFunction()
    {
        $response = Callback::respondFatal('test');
        $object = json_decode($response, null, 512, JSON_THROW_ON_ERROR);

        $this->assertIsObject($object);

        $jsonData = base64_decode($object->data, true);

        // Если не возникает exception, значит данные формируются корректно
        $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
    }
}
