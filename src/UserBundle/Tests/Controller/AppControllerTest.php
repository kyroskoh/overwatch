<?php

namespace Overwatch\UserBundle\Tests\Controller;

use Overwatch\UserBundle\Tests\Base\FunctionalTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * AppControllerTest
 * Functional test for the index route provided by AppController
 */
class AppControllerTest extends FunctionalTestCase
{
    public function testIndexPage()
    {
        $this->logIn('ROLE_USER');
        $this->client->request('GET', '/');
        
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains('<div data-ng-view>', $this->getResponseContent(true));
        $this->assertNotContains('Manage Users', $this->getResponseContent(true));
    }
    
    public function testIndexPageAsSuperAdmin()
    {
        $this->logIn('ROLE_SUPER_ADMIN');
        $this->client->request('GET', '/');
        
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertContains('<div data-ng-view>', $this->getResponseContent(true));
        $this->assertContains('Manage Users', $this->getResponseContent(true));
    }
    
    public function testApiDocPage()
    {
        $this->logIn('ROLE_SUPER_ADMIN');
        $this->client->request('GET', '/api/doc');
        
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
    
    protected function logIn($role)
    {
        $session = $this->client->getContainer()->get('session');
        $firewall = 'overwatch';
        
        $token = new UsernamePasswordToken('testUser', null, $firewall, [$role]);
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
