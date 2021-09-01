<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\MoneyMailRu;
use App\MoneyMailRuException;

class MoneyMailRuTest extends TestCase
{
    /**
     * Test that connection to Mailru works.
     *
     * @return void
     */
    public function test_connects()
    {
        $module = new MoneyMailRu();
        $result = $module->request('merchant/info');
        $this->assertTrue($result['result_code'] === 0);
    }

    /**
     * Test that callback interface exists.
     *
     * @return void
     */
    public function test_callback_interface_exists()
    {
        $response = $this->post(
            '/api/mailru',
            []
        );

        $response->assertStatus(200);
    }
}
