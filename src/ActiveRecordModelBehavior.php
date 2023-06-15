<?php

namespace yii1tech\web\user;

use CBehavior;
use CEvent;

/**
 * ActiveRecordModelBehavior allows operating ActiveRecord model at the WebUser component level.
 *
 * Application configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'user' => [
 *             'class' => yii1tech\web\user\WebUser::class,
 *             'behaviors' => [
 *                 'modelBehavior' => [
 *                     'class' => yii1tech\web\user\ActiveRecordModelBehavior::class,
 *                     'modelClass' => app\models\User::class,
 *                 ],
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * Model access example:
 *
 * ```php
 * $model = Yii::app()->user->getModel();
 * ```
 *
 * @property \yii1tech\web\user\WebUser $owner
 * @property \CActiveRecord|null $model
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ActiveRecordModelBehavior extends CBehavior
{
    /**
     * @var string|\CActiveRecord name of the {@see \CActiveRecord} model class, which stores users.
     */
    public $modelClass;

    /**
     * @var array|string|\CDbCriteria user model search query additional condition or criteria.
     * For example:
     *
     * ```php
     * [
     *     'scopes' => [
     *         'activeOnly',
     *     ],
     * ]
     * ```
     */
    public $modelFindCriteria = '';

    /**
     * @var bool whether to automatically synchronize owner WebUser component with model.
     * @see syncModel()
     */
    public $autoSyncModel = true;

    /**
     * @var array<string, string> map defining which model attribute should be saved as WebUser state on model synchronization.
     * For example:
     *
     * ```php
     * [
     *     'username' => '__name',
     *     'email' => 'email',
     * ]
     * ```
     */
    public $attributeToStateMap = [];

    /**
     * @var array<int, \CActiveRecord> stores related model instance.
     */
    private $_model = [];

    /**
     * Returns model matching currently authenticated user.
     *
     * @return \CActiveRecord|null user model, `null` - if not found.
     */
    public function getModel()
    {
        $userId = $this->owner->getId();

        if (empty($userId)) {
            return null;
        }

        if (!array_key_exists($userId, $this->_model)) {
            $this->_model = [
                $userId => $this->findModel($userId),
            ];
        }

        return $this->_model[$userId];
    }

    /**
     * Sets the user model, changing authenticated user' ID at related WebUser component.
     *
     * > Note: this method can be used for user identity switching, however it is not equal
     *   to {@see \CWebUser::login()} or {@see \CWebUser::changeIdentity()}.
     *
     * @param \CActiveRecord|null $model user model.
     * @return \yii1tech\web\user\WebUser|static owner WebUser component.
     */
    public function setModel($model)
    {
        if (empty($model)) {
            $this->_model = [];

            $this->owner->setId(null);

            return $this->owner;
        }

        $userId = $model->getPrimaryKey();

        $this->_model = [
            $userId => $model,
        ];

        $this->owner->setId($userId);

        return $this->syncModel();
    }

    /**
     * Finds user model by the given ID.
     *
     * @param mixed $userId user's ID.
     * @return \CActiveRecord|null user model, `null` - if not found.
     */
    protected function findModel($userId)
    {
        return $this->modelClass::model($this->modelClass)
            ->findByPk($userId, $this->modelFindCriteria);
    }

    /**
     * Synchronizes owner WebUser component with the model.
     * If related model does not exist performs logout.
     * If related model does exist - synchronizes WebUser states with it.
     *
     * @return \yii1tech\web\user\WebUser|static owner WebUser component.
     */
    public function syncModel()
    {
        if ($this->owner->getIsGuest()) {
            return $this->owner;
        }

        $model = $this->getModel();
        if (empty($model)) {
            $this->owner->logout(false);
        } else {
            foreach ($this->attributeToStateMap as $attribute => $state) {
                $this->owner->setState($state, $model->{$attribute});
            }
        }

        return $this->owner;
    }

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            'onAfterRestore' => 'afterRestore',
        ];
    }

    /**
     * Responds to {@see \yii1tech\web\user\WebUser::$onAfterRestore} event.
     *
     * @param \CEvent $event event parameter.
     */
    public function afterRestore(CEvent $event): void
    {
        if ($this->autoSyncModel) {
            $this->syncModel();
        }
    }
}