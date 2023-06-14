<?php

namespace yii1tech\web\user;

use CEvent;
use CWebUser;

/**
 * WebUser extends the standard Yii class {@see \CWebUser}, providing handlers for the authentication flow events.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'user' => [
 *             'class' => yii1tech\web\user\WebUser::class,
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * @property callable|\CList $onAfterRestore raises after user data restoration.
 * @property callable|\CList $onBeforeLogin raises before user logs in.
 * @property callable|\CList $onAfterLogin raises after user successfully logged in.
 * @property callable|\CList $onBeforeLogout raises before user logs out.
 * @property callable|\CList $onAfterLogout raises after user successfully logged out.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class WebUser extends CWebUser
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (!$this->getIsGuest()) {
            $this->afterRestore();
        }
    }

    /**
     * This method is invoked after a user data has been restored from any source:
     * session, autologin cookies etc.
     * The default implementation raises the {@see onAfterRestore} event.
     * You may override this method to do postprocessing after component initialization.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    protected function afterRestore(): void
    {
        if ($this->hasEventHandler('onAfterRestore')) {
            $this->onAfterRestore(new CEvent($this));
        }
    }

    /**
     * This event is raised after the user data has been restored from any source:
     * session, autologin cookies etc.
     * @param \CEvent $event the event parameter
     */
    public function onAfterRestore($event): void
    {
        $this->raiseEvent('onAfterRestore', $event);
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeLogin($id, $states, $fromCookie)
    {
        if (!$this->hasEventHandler('onBeforeLogin')) {
            return true;
        }

        $event = new CEvent($this, [
            'allowLogin' => true,
            'id' => $id,
            'states' => $states,
            'fromCookie' => $fromCookie,
        ]);
        $this->onBeforeLogin($event);

        return $event->params['allowLogin'];
    }

    /**
     * This event is raised before logging in a user.
     * @param \CEvent $event the event parameter
     */
    public function onBeforeLogin($event): void
    {
        $this->raiseEvent('onBeforeLogin', $event);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterLogin($fromCookie)
    {
        if ($this->hasEventHandler('onAfterLogin')) {
            $this->onAfterLogin(new CEvent($this, [
                'fromCookie' => $fromCookie,
            ]));
        }
    }

    /**
     * This event is raised after the user is successfully logged in.
     * @param \CEvent $event the event parameter
     */
    public function onAfterLogin($event): void
    {
        $this->raiseEvent('onAfterLogin', $event);
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeLogout()
    {
        if (!$this->hasEventHandler('onBeforeLogout')) {
            return true;
        }

        $event = new CEvent($this, [
            'allowLogout' => true,
        ]);

        $this->onBeforeLogout($event);

        return $event->params['allowLogout'];
    }

    /**
     * This event is raised before logging out a user.
     * @param \CEvent $event the event parameter
     */
    public function onBeforeLogout($event): void
    {
        $this->raiseEvent('onBeforeLogout', $event);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterLogout()
    {
        if ($this->hasEventHandler('onAfterLogout')) {
            $this->onAfterLogout(new CEvent($this));
        }
    }

    /**
     * This event is raised after a user is logged out.
     * @param \CEvent $event the event parameter
     */
    public function onAfterLogout($event): void
    {
        $this->raiseEvent('onAfterLogout', $event);
    }
}