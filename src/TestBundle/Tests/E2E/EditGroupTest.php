<?php

namespace Overwatch\TestBundle\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Overwatch\TestBundle\DataFixtures\ORM\TestGroupFixtures;
use Overwatch\UserBundle\DataFixtures\ORM\UserFixtures;
use Overwatch\UserBundle\Tests\Base\WebDriverTestCase;

/**
 * EditGroupTest
 * Tests the edit group view
 */
class EditGroupTest extends WebDriverTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->logInAsUser('user-1');
        $this->waitForLoadingAnimation();
        $this->webDriver->findElement(
            //First group's edit group button
            WebDriverBy::cssSelector('.groups .widget-box:nth-child(1) .widget-header .right a:nth-child(1)')
        )->click();
        $this->waitForLoadingAnimation();
    }

    public function testDisplaysGroupAndUsers()
    {
        $this->assertContains(TestGroupFixtures::$groups['group-1']->getName(), $this->getHeaderText());
        $this->assertCount(2, $this->getUsers());
        $this->assertContains(strtoupper(UserFixtures::$users['user-1']->getEmail()), $this->getUsers()[0]->getText());
        $this->assertContains(strtoupper(UserFixtures::$users['user-2']->getEmail()), $this->getUsers()[1]->getText());
    }

    public function testRenameGroup()
    {
        $this->webDriver->findElement(
            WebDriverBy::cssSelector('.edit-group .edit-group-buttons a:nth-child(1)')
        )->click();

        $this->waitForAlert();
        $this->webDriver->switchTo()->alert()->dismiss();
        $this->assertContains(TestGroupFixtures::$groups['group-1']->getName(), $this->getHeaderText());

        $this->webDriver->findElement(
            WebDriverBy::cssSelector('.edit-group .edit-group-buttons a:nth-child(1)')
        )->click();

        $this->waitForAlert();
        $this->webDriver->switchTo()->alert()->sendKeys('Group Number One');
        $this->webDriver->switchTo()->alert()->accept();
        $this->waitForLoadingAnimation();
        $this->assertContains('Group Number One', $this->getHeaderText());

        $this->webDriver->get('http://127.0.0.1:8000/#/');
        $this->waitForLoadingAnimation();
        $this->assertContains('Group Number One', $this->webDriver->findElements(
            WebDriverBy::cssSelector('.groups .widget-box')
        )[0]->getText());
    }

    public function testChangeGroupMembership()
    {
        $this->getUsers()[0]->findElement(
            WebDriverBy::tagName('a')
        )->click();
        $this->waitForAlert();
        $this->webDriver->switchTo()->alert()->dismiss();
        $this->assertCount(2, $this->getUsers());

        $this->getUsers()[0]->findElement(
            WebDriverBy::tagName('a')
        )->click();
        $this->waitForAlert();
        $this->webDriver->switchTo()->alert()->accept();
        $this->waitForLoadingAnimation();
        $this->assertCount(1, $this->getUsers());

        $this->webDriver->findElement(
            WebDriverBy::cssSelector('.edit-group-buttons a:nth-child(2)')
        )->click();
        $this->waitForAlert();
        $this->webDriver->switchTo()->alert()->dismiss();
        $this->assertCount(1, $this->getUsers());

        $this->webDriver->findElement(
            WebDriverBy::cssSelector('.edit-group-buttons a:nth-child(2)')
        )->click();
        $this->waitForAlert();
        $this->webDriver->switchTo()->alert()->sendKeys(UserFixtures::$users['user-1']->getEmail());
        $this->webDriver->switchTo()->alert()->accept();
        $this->waitForLoadingAnimation();
        $this->assertCount(2, $this->getUsers());
    }

    private function getUsers()
    {
        return $this->webDriver->findElements(
            WebDriverBy::className('user')
        );
    }
}
