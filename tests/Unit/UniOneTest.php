<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\UniOne;
use App\UniOneMessage;
use App\UniOneException;

class UniOneTest extends TestCase
{
    /**
     * Test that connection to Unisender works.
     *
     * @return void
     */
    public function test_connects()
    {
        $unione = new UniOne(config('services.unione.api_key'));
        $result = $unione->systemInfo();
        $this->assertTrue($result['status'] === 'success');
    }

    /**
     * Test that body() throws an exception on empty UniOneMessage.
     *
     * @return void
     */
    public function test_empty_message()
    {
        $message = new UniOneMessage();
        
        $this->expectException(UniOneException::class);
        $message->build();
    }

    /**
     * Test that UniOneMessage->body() returns correct data.
     *
     * @return void
     */
    public function test_message_body()
    {
        $message = new UniOneMessage();
        $message->to('null@vic-insurance.ru', 'No Name');
        $message->subject('subject');
        $message->plain('body');
        $this->assertIsArray($message->build());

        $result = $message->send();
        $this->assertTrue($result['status'] === 'success');
    }

}
