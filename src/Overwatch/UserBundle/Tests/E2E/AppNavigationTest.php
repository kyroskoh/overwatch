<?php

namespace Overwatch\UserBundle\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Overwatch\TestBundle\DataFixtures\ORM\TestFixtures;
use Overwatch\TestBundle\DataFixtures\ORM\TestGroupFixtures;
use Overwatch\UserBundle\DataFixtures\ORM\UserFixtures;
use Overwatch\UserBundle\Tests\Base\WebDriverTestCase;

/**
 * MyAccountTest
 *
 * @author Zac Sturgess <zac.sturgess@wearetwogether.com>
 */
class AppNavigationTest extends WebDriverTestCase
{
    private $pages = [
        '#/',
        '#/users',
        '#/alerts',
        '#/my-account'
    ];
    
    public function setUp()
    {
        parent::setUp();
        
        $groupId = TestGroupFixtures::$groups['group-1']->getId();
        if (!in_array("#/group/$groupId", $this->pages)) {
            $this->pages[] = "#/group/$groupId";
        }
        if (!in_array("#/group/$groupId/add-test", $this->pages)) {
            $this->pages[] = "#/group/$groupId/add-test";
        }
        
        $testId = TestFixtures::$tests['test-1']->getId();
        if (!in_array("#/test/$testId", $this->pages)) {
            $this->pages[] = "#/test/$testId";
        }
        
        $this->logInAsUser('user-1');
        $this->waitForLoadingAnimation();
    }
    
    public function testFirstBreadcrumbLinksHome()
    {
        foreach ($this->pages as $page) {
            $this->webDriver->get('http://127.0.0.1:8000/' . $page);
            $this->waitForLoadingAnimation();
            
            $this->assertEquals('Overwatch', $this->getBreadcrumbs()[0]->getText(), "The breadcrumb text on $page does not equal 'Overwatch'");
            $this->assertEquals(
                'http://127.0.0.1:8000/#/',
                $this->getBreadcrumbs()[0]->findElement(
                    WebDriverBy::cssSelector('a')
                )->getAttribute('href'),
                "The breadcrumb link on $page does not equal '#/'"
            );
        }
    }
    
    public function testCountBreadcrumbs()
    {
        foreach ($this->pages as $page) {
            $this->webDriver->get('http://127.0.0.1:8000/' . $page);
            $this->waitForLoadingAnimation();
            
            $this->assertEquals(2, count($this->getBreadcrumbs()), "Breadcrumb count on $page does not equal 2");
        }
    }
    
    public function testLastBreadcrumbMatchesPageTitle()
    {
        foreach ($this->pages as $page) {
            $this->webDriver->get('http://127.0.0.1:8000/' . $page);
            $this->waitForLoadingAnimation();
            
            $breadcrumbs = $this->getBreadcrumbs();
            
            $this->assertContains(
                $this->webDriver->findElement(
                    WebDriverBy::cssSelector('#page h1')
                )->getText(),
                end($breadcrumbs)->getText(),
                "Final breadcrumb text on $page does not match the page's title"
            );
        }
    }
    
    public function testApiErrorCausesPageRefresh()
    {
        //Remove the currently logged in user from the DB to force the API call to fail
        $this->em->remove(
            $this->em->find('Overwatch\UserBundle\Entity\User', UserFixtures::$users['user-1']->getId())
        );
        $this->em->flush();
        
        $this->webDriver->findElement(
            WebDriverBy::cssSelector('.tests li:nth-child(1) .test a:nth-child(3)')
        )->click();
        
        $this->waitForLoadingAnimation();
        $this->assertContains('http://127.0.0.1:8000/login#/test/', $this->webDriver->getCurrentURL());
    }
    
    private function getBreadcrumbs()
    {
        return $this->webDriver->findElements(
            WebDriverBy::cssSelector('ul.breadcrumbs li:not(.ng-hide)')
        );
    }
}
