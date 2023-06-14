<?php

namespace yii1tech\web\user\test;

use CEvent;
use CUserIdentity;
use Yii;
use yii1tech\web\user\WebUser;

class WebUserTest extends TestCase
{
    protected function createWebUser(): WebUser
    {
        return Yii::createComponent([
            'class' => WebUser::class,
            'stateKeyPrefix' => '',
        ]);
    }

    public function testBeforeLoginEvent(): void
    {
        $webUser = $this->createWebUser();

        $event = null;
        $webUser->onBeforeLogin = function (CEvent $raisedEvent) use (&$event) {
            $event = $raisedEvent;
        };

        $identity = new CUserIdentity('username', 'password');
        $identity->setPersistentStates(['foo' => 'bar']);

        $webUser->login($identity);

        $this->assertFalse($webUser->getIsGuest());

        $this->assertTrue($event instanceof CEvent);
        $this->assertEquals(true, $event->params['allowLogin']);
        $this->assertEquals(false, $event->params['fromCookie']);
        $this->assertEquals($identity->getId(), $event->params['id']);
        $this->assertEquals($identity->getPersistentStates(), $event->params['states']);
    }

    /**
     * @depends testBeforeLoginEvent
     */
    public function testBeforeLoginEventBlocksLogin(): void
    {
        $webUser = $this->createWebUser();

        $webUser->onBeforeLogin = function (CEvent $event) {
            $event->params['allowLogin'] = false;
        };

        $identity = new CUserIdentity('username', 'password');

        $webUser->login($identity);

        $this->assertTrue($webUser->getIsGuest());
    }

    public function testAfterLoginEvent(): void
    {
        $webUser = $this->createWebUser();

        $event = null;
        $webUser->onAfterLogin = function (CEvent $raisedEvent) use (&$event) {
            $event = $raisedEvent;
        };

        $identity = new CUserIdentity('username', 'password');

        $webUser->login($identity);

        $this->assertFalse($webUser->getIsGuest());

        $this->assertTrue($event instanceof CEvent);
        $this->assertEquals(false, $event->params['fromCookie']);
    }

    public function testBeforeLogoutEvent(): void
    {
        $webUser = $this->createWebUser();

        $event = null;
        $webUser->onBeforeLogout = function (CEvent $raisedEvent) use (&$event) {
            $event = $raisedEvent;
        };

        $identity = new CUserIdentity('username', 'password');

        $webUser->login($identity);
        $webUser->logout();

        $this->assertTrue($webUser->getIsGuest());

        $this->assertTrue($event instanceof CEvent);
        $this->assertEquals(true, $event->params['allowLogout']);
    }

    /**
     * @depends testBeforeLogoutEvent
     */
    public function testBeforeLogoutEventBlocksLogout(): void
    {
        $webUser = $this->createWebUser();

        $webUser->onBeforeLogout = function (CEvent $event) {
            $event->params['allowLogout'] = false;
        };

        $identity = new CUserIdentity('username', 'password');

        $webUser->login($identity);
        $webUser->logout();

        $this->assertFalse($webUser->getIsGuest());
    }

    public function testAfterLogoutEvent(): void
    {
        $webUser = $this->createWebUser();

        $event = null;
        $webUser->onAfterLogout = function (CEvent $raisedEvent) use (&$event) {
            $event = $raisedEvent;
        };

        $identity = new CUserIdentity('username', 'password');

        $webUser->login($identity);
        $webUser->logout();

        $this->assertTrue($webUser->getIsGuest());

        $this->assertTrue($event instanceof CEvent);
    }

    public function testAfterRestoreEvent(): void
    {
        $webUser = $this->createWebUser();

        $event = null;
        $webUser->onAfterRestore = function (CEvent $raisedEvent) use (&$event) {
            $event = $raisedEvent;
        };

        Yii::app()->getComponent('session')->open();

        $_SESSION['__id'] = 'username';
        $_SESSION['__name'] = 'username';

        $webUser->init();

        $this->assertTrue($event instanceof CEvent);
    }
}