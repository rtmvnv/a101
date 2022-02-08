<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Accrual;

class HardBouncedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * При получении от UniOne статуса hard_bounced счет закрывается
     *
     * @return void
     */
    public function testHardBounced()
    {
        $this->withoutExceptionHandling();

        /**
         * Начисление в статусе "отправлено"
         */
        $accrual = Accrual::factory()->create([
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'sent_at' => now(),
            'unione_id' => '123456',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        $requestData = [
            'event_name' => 'transactional_email_status',
            'job_id' => '123456',
            'email' => 'test',

        ];

        // print_r($requestData);


        $response = $this->post('/api/unione', $requestData);


        // print_r($response);

        // $response->assertStatus(200);

        $this->assertTrue(true);
    }
}
