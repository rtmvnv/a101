<?php

namespace Tests\Unit;

use Tests\TestCase;

class MoneyMailRuTest extends TestCase
{
    /**
     * Test that our API interface for callbacks exists.
     *
     * @return void
     */
    public function testCallbackInterfaceExists()
    {
        $response = $this->post(
            '/api/mailru',
            []
        );

        $response->assertStatus(200);
    }
}
