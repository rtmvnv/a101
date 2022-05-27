<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use orangedata\orangedata_client;

class OrangeDataTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function _testReceiptIsSent()
    {
        /*
         * Mock the Orange Data class not to send real receipts
         */
        $mock = $this->mock(orangedata_client::class, function (MockInterface $mock) {
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
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testOrangeDataInterface()
    {
        $client = [
            'inn' => env('ORANGEDATA_INN'),
            'api_url' => env('ORANGEDATA_URL'),
            'sign_pkey' => storage_path('app/orangedata/private_key.pem'),
            'ssl_client_key' => storage_path('app/orangedata/client.key'),
            'ssl_client_crt' => storage_path('app/orangedata/client.crt'),
            'ssl_ca_cert' => storage_path('app/orangedata/cacert.pem'),
            'ssl_client_crt_pass' => env('ORANGEDATA_PASS'),
        ];

        $buyer = new orangedata_client($client);

        $buyer->is_debug(); // for write curl.log file

        $order = [
            'id' => '23423423434',
            'type' => 1,
            'customerContact' => 'example@example.com',
            'taxationSystem' => 0,
            'key' => env('ORANGEDATA_INN'),
            'group' => null,
        ];

        $position = [
            'quantity' => '10',
            'price' => 100,
            'tax' => 1,
            'text' => 'some text',
            'paymentMethodType' => 3,
            'paymentSubjectType' => 1,
            'nomenclatureCode' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/',
            'supplierInfo' => [
                'phoneNumbers' => ['+79266660011', '+79293456723'],
                'name' => 'PAO Example',
            ],
            'supplierINN' => env('ORANGEDATA_INN'),
            'agentType' => 127,
            'agentInfo' => [
                'paymentTransferOperatorPhoneNumbers' => ['+79266660011', '+79293456723'],
                'paymentAgentOperation' => 'some operartion',
                'paymentAgentPhoneNumbers' => ['+79266660011', '+79293456723'],
                'paymentOperatorPhoneNumbers' => ['+79266660011'],
                'paymentOperatorName' => 'OAO ATLANT',
                'paymentOperatorAddress' => 'Address',
                'paymentOperatorINN' => '1234567890',
            ],
            'unitOfMeasurement' => 'kg',
            'additionalAttribute' => 'attribute',
            'manufacturerCountryCode' => '534',
            'customsDeclarationNumber' => 'AD 11/77 from 01.08.2018',
            'excise' => '12.43',
        ];

        $payment = [
            'type' => 16,
            'amount' => 131.23,
        ];

        $agent = [
            'agentType' => 127,
            'paymentTransferOperatorPhoneNumbers' => ['+79998887766', '+76667778899'],
            'paymentAgentOperation' => 'Operation',
            'paymentAgentPhoneNumbers' => ['+79998887766'],
            'paymentOperatorPhoneNumbers' => ['+79998887766'],
            'paymentOperatorName' => 'Name',
            'paymentOperatorAddress' => 'ulitsa Adress, dom 7',
            'paymentOperatorINN' => '3123011520',
            'supplierPhoneNumbers' => ['+79998887766', '+76667778899'],
        ];

        $userAttribute = [
            'name' => 'Like',
            'value' => 'Example',
        ];

        $additional = [
            'additionalAttribute' => 'Attribute',
            'customer' => 'Ivanov Ivan',
            'customerINN' => '0987654321',
        ];

        $vending = [
            'automatNumber' => '21321321123',
            'settlementAddress' => 'Address',
            'settlementPlace' => 'Place',
        ];

        /** Create client new order **/
        $buyer->create_order($order)
        ->add_position_to_order($position)
        ->add_payment_to_order($payment)
        ->add_agent_to_order($agent)
        ->add_user_attribute($userAttribute)
        ->add_additional_attributes($additional)
        ->add_vending_to_order($vending)
        ;

        $result = $buyer->send_order(); // Send order
        echo 'SEND ORDER' . PHP_EOL;
        var_dump($result); // View response

        /** View status of order **/
        $order_status = $buyer->get_order_status(23423423434);
        echo 'ORDER STATUS' . PHP_EOL;
        var_dump($order_status);
    }
}
