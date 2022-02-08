<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Accrual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Faker\Generator as Faker;
use Mockery\MockInterface;
use MongoDB\Collection;
use App\Reports\FailedEmails;

class FailedEmailsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the FailedEmailsReport
     *
     * @return void
     */
    public function testFailedEmailsReport()
    {
        $this->withoutExceptionHandling();

        $faker = app(Faker::class);

        // Mock accrual
        $accrual = Accrual::factory()->create([
            'payee' => 'a101',
            'sum' => 100,
            'email' => 'test1@vic-insurance.ru',
            'period' => date('Ym', strtotime('previous month')),
            'sent_at' => now(),
            'unione_id' => $faker->word(),
            'unione_status' => 'hard_bounced',
            'unione_at' => now(),
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => now(),
        ]);

        // Mock the Events collection
        $mongo_events = $this->mock(Collection::class, function (MockInterface $mock) use ($faker) {
            $mock->shouldReceive('aggregate')
                ->andReturn([
                    [
                        '_id' => 'test1@vic-insurance.ru',
                        'status' => 'hard_bounced',
                        'delivery_status' => 'err_user_unknown',
                        'destination_response' => $faker->sentence(),
                    ],
                ]);
        });
        app()->instance('mongo_events', $mongo_events);

        // Run the FailedEmails report
        $records = (new FailedEmails('-1 month'))();
        $this->assertEquals($records[0]['account'], $accrual['account']);
    }
}
