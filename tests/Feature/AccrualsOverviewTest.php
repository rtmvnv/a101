<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Accrual;
use App\Http\Controllers\Dashboard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccrualsOverviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     *
     *
     * @return void
     */
    public function testAccrualsOverviewPeriod()
    {
        $this->withoutExceptionHandling();

        Accrual::factory()->create([
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
            'archived_at' => now(),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ2',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => now(),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => now(),
            'confirmed_at' => now(),
            'paid_at' => now(),
            'archived_at' => now(),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ3',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => now(),
            'sent_at' => now(),
            'unione_status' => 'delivered',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => null,
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ2',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => new Carbon('previous month'),
            'unione_status' => 'delivered',
            'opened_at' => new Carbon('previous month'),
            'confirmed_at' => new Carbon('previous month'),
            'paid_at' => new Carbon('previous month'),
            'archived_at' => new Carbon('previous month'),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ2',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => new Carbon('previous month'),
            'unione_status' => 'hard_bounced',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => new Carbon('previous month'),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ3',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym', strtotime('previous month')),
            'created_at' => new Carbon('previous month'),
            'sent_at' => new Carbon('previous month'),
            'unione_status' => 'hard_bounced',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => new Carbon('previous month'),
        ]);

        $dashboard = new Dashboard();
        $thisMonth = $dashboard->getAccrualsOverviewPeriod('this month');
        $previousMonth = $dashboard->getAccrualsOverviewPeriod('previous month');

        $this->assertEquals(3, $thisMonth['total']);
        $this->assertEquals(3, $thisMonth['sent']);
        $this->assertEquals(0, $thisMonth['not_sent']);
        $this->assertEquals(3, $thisMonth['delivered']);
        $this->assertEquals(0, $thisMonth['not_delivered']);
        $this->assertEquals(2, $thisMonth['paid']);

        $this->assertEquals(3, $previousMonth['total']);
        $this->assertEquals(2, $previousMonth['sent']);
        $this->assertEquals(1, $previousMonth['not_sent']);
        $this->assertEquals(1, $previousMonth['delivered']);
        $this->assertEquals(1, $previousMonth['not_delivered']);
        $this->assertEquals(1, $previousMonth['paid']);
    }

    /**
     *
     *
     * @return void
     */
    public function testAccrualsOverviewDay()
    {
        $this->withoutExceptionHandling();

        Accrual::factory()->create([
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
            'archived_at' => now(),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ1',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => now(),
            'sent_at' => null,
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ2',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => now(),
            'sent_at' => now(),
            'unione_status' => 'spam',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => now(),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ3',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => new Carbon('-24 hours'),
            'sent_at' => new Carbon('-24 hours'),
            'unione_status' => 'opened',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => null,
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ4',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => new Carbon('-24 hours'),
            'sent_at' => new Carbon('-24 hours'),
            'unione_status' => 'opened',
            'opened_at' => now(),
            'confirmed_at' => now(),
            'paid_at' => now(),
            'archived_at' => now(),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ5',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => new Carbon('-24 hours'),
            'sent_at' => new Carbon('-24 hours'),
            'unione_status' => 'hard_bounced',
            'opened_at' => null,
            'confirmed_at' => null,
            'paid_at' => null,
            'archived_at' => now(),
        ]);

        Accrual::factory()->create([
            'account' => 'ТЕСТ6',
            'payee' => 'a101',
            'sum' => 100,
            'period' => date('Ym'),
            'created_at' => new Carbon('-24 hours'),
            'sent_at' => new Carbon('-24 hours'),
            'unione_status' => 'clicked',
            'opened_at' => now(),
            'confirmed_at' => now(),
            'paid_at' => now(),
            'archived_at' => now(),
        ]);

        $dashboard = new Dashboard();
        $today = $dashboard->getAccrualsOverviewDay('today');
        $yesterday = $dashboard->getAccrualsOverviewDay('yesterday');

        $this->assertEquals(2, $today['total']);
        $this->assertEquals(2, $today['sent']);
        $this->assertEquals(1, $today['not_sent']);
        $this->assertEquals(1, $today['delivered']);
        $this->assertEquals(1, $today['not_delivered']);
        $this->assertEquals(1, $today['paid']);

        $this->assertEquals(4, $yesterday['total']);
        $this->assertEquals(4, $yesterday['sent']);
        $this->assertEquals(0, $yesterday['not_sent']);
        $this->assertEquals(3, $yesterday['delivered']);
        $this->assertEquals(1, $yesterday['not_delivered']);
        $this->assertEquals(2, $yesterday['paid']);
    }
}
