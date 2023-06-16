<p align="center">
    <a href="https://github.com/yii1tech" target="_blank">
        <img src="https://avatars.githubusercontent.com/u/134691944" height="100px">
    </a>
    <h1 align="center">Advanced web user component for Yii 1</h1>
    <br>
</p>

This extension provides advanced web user component for Yii 1.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://img.shields.io/packagist/v/yii1tech/web-user.svg)](https://packagist.org/packages/yii1tech/web-user)
[![Total Downloads](https://img.shields.io/packagist/dt/yii1tech/web-user.svg)](https://packagist.org/packages/yii1tech/web-user)
[![Build Status](https://github.com/yii1tech/web-user/workflows/build/badge.svg)](https://github.com/yii1tech/web-user/actions)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii1tech/web-user
```

or add

```json
"yii1tech/web-user": "*"
```

to the "require" section of your composer.json.


Usage
-----

This extension provides advanced version of the standard `CWebUser` component for Yii 1.
Class `yii1tech\web\user\WebUser` adds ability for setup external handlers for the authentication flow events.
Following events are available:

- 'onAfterRestore' - raises after user data restoration from session, cookie and so on.
- 'onBeforeLogin' - raises before user logs in.
- 'onAfterLogin' - raises after user successfully logged in.
- 'onBeforeLogout' - raises before user logs out.
- 'onAfterLogout' - raises after user successfully logged out.

Application configuration example:

```php
<?php

return [
    'components' => [
        'user' => [
            'class' => yii1tech\web\user\WebUser::class,
            'onAfterLogin' => function (CEvent $event) {
                Yii::log('Login User ID=' . $event->sender->getId());
            },
        ],
        // ...
    ],
    // ...
];
```

The most notable of all introduced events is 'onAfterRestore'.
By default, Yii does not re-check user's identity availability on each subsequent request. Once user logs in, he stays authenticated, even
if related record at "users" table is deleted. You can use 'onAfterRestore' event to ensure deleted or banned users will lose access to your
application right away. For example:

```php
<?php

return [
    'components' => [
        'user' => [
            'class' => yii1tech\web\user\WebUser::class,
            'onAfterRestore' => function (CEvent $event) {
                $user = User::model()->findByPk($event->sender->getId());
                
                if (empty($user) || $user->is_banned) {
                    $event->sender->logout(false);
                }
            },
        ],
        // ...
    ],
    // ...
];
```


## Operating ActiveRecord model via WebUser

This package also provides `yii1tech\web\user\ActiveRecordModelBehavior` behavior for the `yii1tech\web\user\WebUser`, which allows
operating ActiveRecord model at the WebUser component level.

Application configuration example:

```php
<?php

return [
    'components' => [
        'user' => [
            'class' => yii1tech\web\user\WebUser::class,
            'behaviors' => [
                'modelBehavior' => [
                    'class' => yii1tech\web\user\ActiveRecordModelBehavior::class,
                    'modelClass' => app\models\User::class, // ActiveRecord class to used for model source
                    'attributeToStateMap' => [ // map for WebUser states fill up from ActiveRecord model attributes
                        'username' => '__name', // matches `Yii::app()->user->getName()`
                        'email' => 'email', // matches `Yii::app()->user->getState('email')`
                    ],
                ],
            ],
        ],
        // ...
    ],
    // ...
];
```

Inside you program you can always access currently authenticated user's model via "user" application component.
For example:

```php
<?php

$user = Yii::app()->user->getModel();

var_dump($user->id == Yii::app()->user->getId()); // outputs `true`
var_dump($user->username == Yii::app()->user->getName()); // outputs `true`
var_dump($user->email == Yii::app()->user->getState('email')); // outputs `true`

$user->setAttributes($_POST['User']);
$user->save();
```

In case there is no authenticated user `yii1tech\web\user\ActiveRecordModelBehavior::getModel()` returns `null`.
For example:

```php
<?php

$user = Yii::app()->user->getModel();
if ($user) {
    var_dump(Yii::app()->user->getIsGuest()); // outputs `false`
} else {
    var_dump(Yii::app()->user->getIsGuest()); // outputs `true`
}
```

By default `yii1tech\web\user\ActiveRecordModelBehavior` automatically logs out any authenticated user, if it is unable to get his
related record from database. You may control this behavior via `yii1tech\web\user\ActiveRecordModelBehavior::$autoSyncModel`.

You may add extra condition for the user search query via `yii1tech\web\user\ActiveRecordModelBehavior::$modelFindCriteria`.
This allows you to handle such things as user's ban or account confirmation. For example:

```php
<?php

return [
    'components' => [
        'user' => [
            'class' => yii1tech\web\user\WebUser::class,
            'behaviors' => [
                'modelBehavior' => [
                    'class' => yii1tech\web\user\ActiveRecordModelBehavior::class,
                    'modelClass' => app\models\User::class,
                    'modelFindCriteria' => [
                        'scopes' => [
                            'activeOnly',
                        ],
                        'condition' => 'is_banned = 0',
                    ],
                ],
            ],
        ],
        // ...
    ],
    // ...
];
```

You may use `yii1tech\web\user\ActiveRecordModelBehavior::setModel()` method to switch user identity. For example:

```php
<?php

$user = User::model()->findByPk(1);

Yii::app()->user->setModel($user);

var_dump(Yii::app()->user->getIsGuest()); // outputs `false`
var_dump($user->id == Yii::app()->user->getId()); // outputs `true`
```

> Note: while method `yii1tech\web\user\ActiveRecordModelBehavior::setModel()` can be used for user identity switching, 
  it is not equal to `\CWebUser::login()` or `\CWebUser::changeIdentity()`, since it does not handle related Cookies
  and some other related features.

You may use `yii1tech\web\user\ActiveRecordModelBehavior::setModel()` in junction with ["yii1tech/session-dummy"](https://github.com/yii1tech/session-dummy)
to easily create authentication flow for API. For example:

```php
<?php

namespace app\web\controllers;

use app\models\OAuthToken;
use app\models\User;
use CController;
use Yii;
use yii1tech\session\dummy\DummySession;

class ApiController extends CController
{
    public function init()
    {
        parent::init();
        
        // mock session, so it does not send any Cookies to the API client:
        Yii::app()->setComponent('session', new DummySession(), false);
        
        // find OAuth token matching request:
        $oauthToken = OAuthToken::model()->findByPk(Yii::app()->request->getParam('oauth_token'));
        if (!$oauthToken) {
            return;
        }
        
        // find User matching OAuth token:
        $user = User::model()->findByPk($oauthToken->user_id);
        if (!$user) {
            return;
        }
        
        // act as found user:
        Yii::app()->user->setModel($user);
    }
    
    public function filters()
    {
        return [
            'accessControl', // now we can freely use standard "access control" filter and other features
        ];
    }
    
    public function accessRules()
    {
        return [
            // ...
        ];
    }
    
    // ...
}
```