<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use App\A101;
use App\Models\Accrual;
use orangedata\orangedata_client;

class SendChequeTest extends TestCase
{
    use RefreshDatabase;

    /*
     * Проверка метода $a101->sendCheque().
     * Метод получает $accrual и должен сформировать правильный запрос к Orange Data
     */
    public function test_send_cheque()
    {
        /*
         * Create an accrual
         */
        $accrual = Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => now(),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => now(),
            'confirmed_at' => now(),
            'paid_at' => now(),
            'archived_at' => null,
        ]);

        $a101 = new A101();

        /*
         * Mock OrangeData not to send real requests.
         * Normally orangedata_client() constructor parameters
         * are set in AppServiceProvider, but that doesn't work for mocks.
         */
        $mockOrangeData = Mockery::mock(orangedata_client::class, [[
            'inn' => config('services.orangedata.inn'),
            'api_url' => config('services.orangedata.url'),
            'sign_pkey' => storage_path('app/orangedata/private_key.pem'),
            'ssl_client_key' => storage_path('app/orangedata/client.key'),
            'ssl_client_crt' => storage_path('app/orangedata/client.crt'),
            'ssl_ca_cert' => storage_path('app/orangedata/cacert.pem'),
            'ssl_client_crt_pass' => config('services.orangedata.pass'),
        ]])->makePartial();

        $mockOrangeData
            ->shouldReceive('send_order')
            ->times(5)
            ->andReturn(
                ['errors' => ['test 27761839']], // false
                '{"errors" : []}', // true
                '{"errors" : ["test 86236337"]}', //false
                'test 96716959', // false
                ['errors' => []], // true
            )
            ->shouldReceive('send_order')
            ->times(1)
            ->andThrow(new \Exception('exception 75713322'));

        $this->instance(orangedata_client::class, $mockOrangeData);

        $this->assertEquals('test 27761839', $a101->sendCheque($accrual));
        $this->assertTrue($a101->sendCheque($accrual));
        $this->assertEquals('test 86236337', $a101->sendCheque($accrual));
        $this->assertEquals('test 96716959', $a101->sendCheque($accrual));
        $this->assertTrue($a101->sendCheque($accrual));
        $this->assertStringContainsString('exception 75713322', $a101->sendCheque($accrual));
    }
}
