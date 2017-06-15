<?php

namespace AppBundle\Tests\Controller;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpMessage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class DefaultControllerTest extends WebTestCase
{

    private $productConsumer;

    /** @var  AmqpContext */
    private $amqpContext;

    protected function setUp()
    {
        $config = [
            'host' => 'message_bus'
        ];

        $factory = new AmqpConnectionFactory($config);
        $this->amqpContext = $factory->createContext();

        $queue = $this->amqpContext->createQueue('product');
        $this->productConsumer = $this->amqpContext->createConsumer($queue);

    }

    public function productProvider()
    {
        return
            [
                [
                    [
                        "user_id" => 1,
                        "store_id" => 1,
                        "product_name" => "Sony PlayStation 4",
                        "category_id" => 10,
                        "price" => 250
                    ],
                ],

                [
                    [
                        "user_id" => 1,
                        "store_id" => 1,
                        "product_name" => "iPhone 6s",
                        "category_id" => 10,
                        "price" => 500
                    ],
                ],

                [
                    [
                        "user_id" => 1,
                        "store_id" => 1,
                        "product_name" => "iPhone 7",
                        "category_id" => 10,
                        "price" => 700
                    ],
                ]
            ];

    }

    /**
     * @dataProvider productProvider
     */
    public function testCreateProduct($product)
    {

        $client = static::createClient();

        $headers = array(
            'CONTENT_TYPE' => 'application/json',
        );

        $client->request('POST', '/create', [], [], $headers, json_encode($product));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"success":true}', $client->getResponse()->getContent());

        $m = $this->productConsumer->receive(1);
        $this->productConsumer->acknowledge($m);

        $this->assertTrue($m instanceof AmqpMessage);

        $testedProduct = json_decode($m->getBody(), true);

        $this->assertTrue(isset($testedProduct['id']));
        $this->assertNotNull($testedProduct['id']);

        /**  */
        unset($testedProduct['id']);
        unset($product['user_id']);

        $this->assertEquals($testedProduct, $product);
    }


    protected function tearDown()
    {
        $queue = $this->amqpContext->createQueue('product');
        $this->amqpContext->purge($queue);
    }

}
