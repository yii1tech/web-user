<?php

namespace yii1tech\web\user;

use CEvent;
use CWebUser;

/**
 * WebUser extends the standard Yii class {@see \CWebUser}, providing handlers for the following events:
 *
 * - {@see onAfterRestore} - raises after user data restoration;
 * - {@see onBeforeLogin} - raises before user logs in;
 * - {@see onAfterLogin} - raises after user successfully logged in;
 * - {@see onBeforeLogout} - raises before user logs out;
 * - {@see onAfterLogout} - raises after user successfully logged out;
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

        $this->afterRestore();
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
        $allowLogin = true;

        if ($this->hasEventHandler('onBeforeLogin')) {
            $this->onBeforeLogin(new CEvent($this, [
                'allowLogin' => &$allowLogin,
                'id' => $id,
                'states' => $states,
                'fromCookie' => $fromCookie,
            ]));
        }

        return $allowLogin;
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
        $allowLogout = true;

        if ($this->hasEventHandler('onBeforeLogout')) {
            $this->onBeforeLogout(new CEvent($this, [
                'allowLogout' => &$allowLogout,
            ]));
        }

        return $allowLogout;
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