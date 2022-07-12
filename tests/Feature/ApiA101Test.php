<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Faker\Generator as Faker;
use App\A101;
use App\UniOne\UniOne;

class ApiA101Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the /api/a101/accruals interface.
     *
     * @return void
     */
    public function testAccruals()
    {
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
         * Проверить, что подпись проверяется
         */
        $requestData =  [
            'sum' => '100',
            'period' => '202202',
            'account' => 'ИК123456',
            'email' => 'null@vic-insurance.ru',
            'name' => 'Имя User-Name',
        ];
        $content = base64_encode(file_get_contents('tests/Feature/XlsxToPdf.pdf'));

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
        $signature = $requestData['sum']
            . $requestData['period']
            . $requestData['account']
            . $requestData['email']
            . $requestData['name'];
        $signature = base64_encode($signature);
        $signature = $signature . env('A101_SIGNATURE');
        $signature = hash('sha1', $signature);
        $requestData['signature'] = $signature;

        $response = $this->call(
            'POST',
            '/api/a101/accruals',
            $requestData,
            array(),
            array(),
            array(),
            $content,
        );
        $response->assertStatus(200);
    }

    /**
     * Test the /api/a101/payments interface.
     *
     * @return void
     */
    public function testPayments()
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

    /**
     * Проверить, что при нехватке полей в запросе возвращается ошибка
     *
     * @return void
     */
    public function testRequiredFields()
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

        // sum field is missing or invalid
        $temp = $data['sum'];
        unset($data['sum']);
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('sum'); // The sum field is required.

        $data['sum'] = 'test';
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('sum');
        $data['sum'] = $temp;

        // period field is missing or invalid
        $temp = $data['period'];
        unset($data['period']);
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('period');

        $data['period'] = 'test';
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('period');
        $data['period'] = $temp;

        // account field is missing or invalid
        $temp = $data['account'];
        unset($data['account']);
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('account');

        $data['account'] = '1 2 a #';
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('account');
        $data['account'] = $temp;

        // name field is missing or invalid
        $temp = $data['name'];
        unset($data['name']);
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('name');

        $data['name'] = 100;
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('name');
        $data['name'] = $temp;

        // email field is missing or invalid
        $temp = $data['email'];
        unset($data['email']);
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('email');

        $data['email'] = 100;
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('email');
        $data['email'] = $temp;

        // signature field is missing or invalid
        $temp = $data['signature'];
        unset($data['signature']);
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('signature');

        $data['signature'] = 'abc';
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(401);
        $response->assertSee('Wrong signature');

        $data['signature'] = '1 2 # $';
        $response = $this->call('POST', '/api/a101/accruals', $data, [], [], [], 'test');
        $response->assertStatus(400);
        $response->assertSee('signature');
        $data['signature'] = $temp;
    }
}
