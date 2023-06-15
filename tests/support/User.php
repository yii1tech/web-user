<?php

namespace yii1tech\web\user\test\support;

use CActiveRecord;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property int $status
 */
class User extends CActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * {@inheritdoc}
     */
    public function tableName()
    {
        return 'user';
    }
}