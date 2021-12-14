<?php

namespace Tests\Unit;

use App\Service;
use App\UniOne\UniOne;
use App\UniOne\Message;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;

class A101ApiTest extends TestCase
{
    /**
     * Test the /api/a101/accruals interface.
     *
     * @return void
     */
    public function test_accruals()
    {
        /*
         * Проверить, что подпись проверяется
         */
        $requestData =  [
            'sum' => '100',
            'period' => '202111',
            'account' => 'ИК123456',
            'email' => 'test@example.com',
            'name' => 'Имя User-Name',
        ];
        $content = 'c2FtcGxlIHBkZiBmaWxl';

        $requestData['signature'] = 'test';
        $response = $this->call(
            'POST',
            '/api/a101/accruals',
            $requestData,
            array(),
            array(),
            array(),
            $content,
        );
        $response->assertStatus(401);

        /*
         * Проверить, что корректный запрос проходит
         */
        // $signature = $requestData['sum']
        //     . $requestData['period']
        //     . $requestData['account']
        //     . $requestData['email']
        //     . $requestData['name'];
        // $signature = base64_encode($signature);
        // $signature = $signature . env('A101_SIGNATURE');
        // $signature = hash('sha1', $signature);
        // $requestData['signature'] = $signature;

        // $response = $this->call(
        //     'POST',
        //     '/api/a101/accruals',
        //     $requestData,
        //     array(),
        //     array(),
        //     array(),
        //     $content,
        // );
        // $response->assertStatus(200);
    }

    /**
     * Test the /api/a101/payments interface.
     *
     * @return void
     */
    public function test_payments()
    {
        $requestData =  [];
        $response = $this->call('GET', '/api/a101/payments', $requestData);
        $response->assertStatus(400);
        $response->assertJson(['title' => 'The from field is required.']);

        $requestData['from'] = 'test';
        $response = $this->call('GET', '/api/a101/payments', $requestData);
        $response->assertStatus(400);
        $response->assertJson(['title' => 'The from field is not a valid date.']);

        $requestData['from'] = '-1 week';
        $response = $this->call('GET', '/api/a101/payments', $requestData);
        $response->assertStatus(400);
        $response->assertJson(['title' => 'The signature field is required.']);

        $requestData['signature'] = 'test';
        $response = $this->call('GET', '/api/a101/payments', $requestData);
        $response->assertStatus(401);
        $response->assertJson(['title' => 'Wrong signature']);

        $signature = $requestData['from'];
        $signature = base64_encode($signature);
        $signature = $signature . env('A101_SIGNATURE');
        $signature = hash('sha1', $signature);
        $requestData['signature'] = $signature;
        $response = $this->call('GET', '/api/a101/payments', $requestData);
        $response->assertStatus(200);
    }
}
