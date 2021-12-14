<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\UniOne\UniOne;
use App\UniOne\Message;
use App\UniOne\Exception;

class UniOneTest extends TestCase
{
    /**
     * Test that connection to Unisender works.
     *
     * @return void
     */
    public function testConnects()
    {
        $unione = new UniOne(config('services.unione.api_key'));
        $result = $unione->systemInfo();
        $this->assertTrue($result['status'] === 'success');
    }

    /**
     * Test that body() throws an exception on empty Message.
     *
     * @return void
     */
    public function testEmptyMessage()
    {
        $message = new Message();

        $this->expectException(Exception::class);
        $message->build();
    }

    /**
     * Test that Message->body() returns correct data.
     *
     * @return void
     */
    public function testMessageBody()
    {
        $message = new Message();
        $message->to('null@vic-insurance.ru', 'No Name');
        $message->subject('subject');
        $message->plain('body');
        $this->assertIsArray($message->build());

        $unione = new UniOne();
        $result = $unione->emailSend($message);
        $this->assertTrue($result['status'] === 'success');
    }
}
