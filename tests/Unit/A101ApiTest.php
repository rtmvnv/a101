<?php

namespace Tests\Unit;

use Tests\TestCase;

class A101ApiTest extends TestCase
{
    /**
     * Test the /api/a101/accruals interface.
     *
     * @return void
     */
    public function test_accruals()
    {
        $response = $this->post('/api/a101/accruals', []);
        $response->assertStatus(400);

        $requestData =  [
            'sum' => '100',
            'period' => '202111',
            'account' => 'TEST',
            'email' => 'test@example.com',
            'name' => 'Имя User-Name',
        ];

        $requestData['signature'] = 'test';
        $response = $this->post('/api/a101/accruals', $requestData);
        $response->assertStatus(401);

        $signature = $requestData['sum']
            . $requestData['period']
            . $requestData['account']
            . $requestData['email']
            . $requestData['name'];
        $signature = base64_encode($signature);
        $signature = $signature . env('A101_SIGNATURE');
        $signature = hash('sha1', $signature);
        $requestData['signature'] = $signature;
        $response = $this->post('/api/a101/accruals', $requestData);
        $response->assertStatus(500);
    }

    /**
     * Test the /api/a101/payments interface.
     *
     * @return void
     */
    public function test_payments()
    {
        $requestData =  [];
        $response = $this->get('/api/a101/payments', $requestData);
        $response->assertStatus(400);

        $requestData['from'] = '100';
        $response = $this->get('/api/a101/payments', $requestData);
        $response->assertStatus(400);

        $requestData['signature'] = 'test';
        $response = $this->get('/api/a101/payments', $requestData);
        $response->assertStatus(401);

        $signature = $requestData['from'];
        $signature = base64_encode($signature);
        $signature = $signature . env('A101_SIGNATURE');
        $signature = hash('sha1', $signature);
        $requestData['signature'] = $signature;
        $response = $this->get('/api/a101/payments', $requestData);
        $response->assertStatus(200);

        print_r($response);

    }
}
