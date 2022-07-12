<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Faker\Generator as Faker;
use App\A101;
use App\UniOne\UniOne;

class MultipleEmailsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testMultipleEmails()
    {
        // $this->withoutExceptionHandling();

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
                ], [
                    'status' => 'success',
                    'job_id' => '103',
                ], [
                    'status' => 'success',
                    'job_id' => '104',
                ], [
                    'status' => 'success',
                    'job_id' => '105',
                ]);
        });
        app()->instance(UniOne::class, $mock);

        /*
         * Create an accrual with an invalid email
         */
        $faker = app(Faker::class);
        $data = [
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'account' => 'БВ' . $faker->randomNumber(6, true),
            'name' => $faker->name(),
            'email' => 'not-an-email',
        ];
        $a101 = app(A101::class);
        $data['signature'] = $a101->postApiAccrualsSignature($data);

        $response = $this->call(
            'POST',
            '/api/a101/accruals',
            $data,
            [],
            [],
            [],
            base64_encode(file_get_contents('tests/Feature/XlsxToPdf.pdf'))
        );
        $response->assertStatus(400);
        $response->assertSee('email');

        /*
         * Create an accrual with multiple emails
         */
        $faker = app(Faker::class);
        $data = [
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'account' => 'БВ' . $faker->randomNumber(6, true),
            'name' => $faker->name(),
            'email' => 'null1@vic-insurance.ru null2@vic-insurance.ru',
        ];
        $a101 = app(A101::class);
        $data['signature'] = $a101->postApiAccrualsSignature($data);

        $response = $this->call(
            'POST',
            '/api/a101/accruals',
            $data,
            [],
            [],
            [],
            base64_encode(file_get_contents('tests/Feature/XlsxToPdf.pdf'))
        );
        $response->assertStatus(200);
    }
}
