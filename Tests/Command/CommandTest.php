<?php

namespace RevisionTen\CQRS\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;

class CommandTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    /**
     * @var Router
     */
    private $router;

    public function setUp()
    {
        $this->client = static::createClient();

        // Add Example Route.
        $this->router = $this->client->getContainer()->get('router');
        $this->router->getRouteCollection()
            ->add('create_page_route', new Route('/create-page/{title}', [
                '_controller' => '\RevisionTen\CQRS\Tests\Examples\Controller\PageController::createPage',
            ]))
        ;
        $this->router->warmUp('var/cache/test/cqrstest');
    }

    public function testCreatePage()
    {
        $this->client->request('GET', '/create-page/TestTitle');
        $response = $this->client->getResponse();
        $content = $response->getContent();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($content);

        $data = json_decode($content, true);

        $this->assertEquals('TestTitle', $data['title']);
        $this->assertNotEmpty($data['uuid']);
    }
}
