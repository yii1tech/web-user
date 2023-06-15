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
            'onAfterLogin' => function (CEvent $raisedEvent) {
                Yii::log('User login ID=' . $raisedEvent->sender->getId());
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
            'onAfterRestore' => function (CEvent $raisedEvent) {
                $user = User::model()->findByPk($raisedEvent->sender->getId());
                
                if (empty($user) || $user->is_banned) {
                    $raisedEvent->sender->logout(false);
                }
            },
        ],
        // ...
    ],
    // ...
];
```

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
                    'attributeToStateMap' => [ // map for WebUser states fill up
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
```
