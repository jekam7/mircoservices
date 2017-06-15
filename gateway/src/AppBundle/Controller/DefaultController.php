<?php

namespace AppBundle\Controller;

use Buzz\Browser;
use Buzz\Message\MessageInterface;
use Buzz\Message\Response;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends FOSRestController
{
    const ACTION_USER_AUTH = 'user_auth';
    const ACTION_PRODUCT_CREATE = 'product_create';

    const CODE_PERMISSION_DENIED = 403;
    const CODE_SUCCESS = 200;

    public function createProductAction(Request $request)
    {
        $userId = $request->get('user_id');

        if ($userId) {
            $response = $this->makeRequest(self::ACTION_USER_AUTH, ['user_id' => $userId]);
            $responseContent = json_decode($response->getContent(), true);
        }
        if (!isset($responseContent) or !isset($responseContent['success']) or !$responseContent['success']) {
            $view = $this->view([
                'success' => false,
                'code' => self::CODE_PERMISSION_DENIED,
                'message' => 'Authorization failed! You have to provide correct user_id'
            ]);

            return $this->handleView($view);
        }

        $data = $request->request->all();

        $response = $this->makeRequest(self::ACTION_PRODUCT_CREATE, $data);
        $view = $this->view(['success' => true, 'code' => self::CODE_SUCCESS, 'data' => $response]);

        return $this->handleView($view);
    }

    /**
     * @param $action
     * @param array $data
     * @return MessageInterface|null
     */
    private function makeRequest($action, array $data)
    {
        /** @var Browser $curl */
        $browser = $this->get('gremo_buzz');

        switch ($action) {
            case self::ACTION_USER_AUTH:
                $response = new Response();
                $response->setContent(json_encode(['success' => true]));

                return $response;
                break;
            case self::ACTION_PRODUCT_CREATE:
                $url = $this->getParameter('product_create_url');
                $headers = ['Content-Type' => 'application/json'];

                return $browser->post($url, $headers, json_encode($data));
                break;
            default:
                return null;
        }
    }
}
