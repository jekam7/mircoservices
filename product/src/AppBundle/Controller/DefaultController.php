<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\Client\Producer;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FOSRestController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $serializer = $this->get('serializer');

        $requestJson = json_encode($request->request->all());
        /** @var Product $product */
        $product = $serializer->deserialize($requestJson, Product::class, 'json');

        if (!$product->getName() || !$product->getStoreId() || !$product->getCategoryId() || !$product->getPrice()) {
            $response = ['success' => false, 'message' => 'Product fields cannot be empty.'];
        } else {
            $em = $this->get('doctrine')->getManager();

            $em->persist($product);
            $em->flush();

            $this->sendToMessageBus($product);

            $response = ['success' => true];
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }

    private function sendToMessageBus($product)
    {
        $serializer = $this->get('serializer');
        $data = $serializer->serialize($product, 'json');

        $config = [
            'host' => 'message_bus'
        ];

        $factory = new AmqpConnectionFactory($config);
        $context = $factory->createContext();

        $topic = $context->createTopic('microservices');
        $topic->addFlag(AMQP_DURABLE);
        $topic->setType(AMQP_EX_TYPE_FANOUT);
        $topic->setArguments(['alternate-exchange' => 'product']);

        $context->declareTopic($topic);

        $fooQueue = $context->createQueue('product');
        $fooQueue->addFlag(AMQP_DURABLE);

        $context->declareQueue($fooQueue);

        $context->bind($topic, $fooQueue);

        $message = $context->createMessage($data);
        $context->createProducer()->send($fooQueue, $message);

    }
}
