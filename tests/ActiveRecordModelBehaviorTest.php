<?php

namespace yii1tech\web\user\test;

use Yii;
use yii1tech\web\user\ActiveRecordModelBehavior;
use yii1tech\web\user\test\support\User;
use yii1tech\web\user\WebUser;

class ActiveRecordModelBehaviorTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDbSchema();
        $this->seedTestDb();
    }

    /**
     * @return string test table name
     */
    protected function getTestTableName(): string
    {
        return 'user';
    }

    /**
     * Creates test config table.
     */
    protected function createTestDbSchema(): void
    {
        $dbConnection = Yii::app()->db;
        $columns = [
            'id' => 'pk',
            'username' => 'string',
            'email' => 'string',
            'status' => 'int',
        ];
        $dbConnection->createCommand()->createTable($this->getTestTableName(), $columns);
    }

    /**
     * Inserts test data to the database.
     */
    protected function seedTestDb(): void
    {
        $dbConnection = Yii::app()->db;

        $dbConnection->getCommandBuilder()->createMultipleInsertCommand($this->getTestTableName(), [
            [
                'username' => 'active-user',
                'email' => 'active-user@example.test',
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'username' => 'inactive-user',
                'email' => 'inactive-user@example.test',
                'status' => User::STATUS_INACTIVE,
            ],
        ])->execute();
    }

    /**
     * @param array $behaviorConfig
     * @return \yii1tech\web\user\WebUser|\yii1tech\web\user\ActiveRecordModelBehavior
     */
    protected function createWebUser(array $behaviorConfig = []): WebUser
    {
        $user = Yii::createComponent([
            'class' => WebUser::class,
            'stateKeyPrefix' => '',
            'behaviors' => [
                'modelBehavior' => array_merge([
                    'class' => ActiveRecordModelBehavior::class,
                    'modelClass' => User::class,
                ], $behaviorConfig),
            ],
        ]);

        $user->init();

        return $user;
    }

    public function testGetModel(): void
    {
        $webUser = $this->createWebUser();

        $this->assertNull($webUser->getModel());

        $webUser->setId(1);

        $model = $webUser->getModel();

        $this->assertTrue($model instanceof User);

        $webUser->setId(99);

        $this->assertNull($webUser->getModel());
    }

    /**
     * @depends testGetModel
     */
    public function testSetModel(): void
    {
        $webUser = $this->createWebUser();

        $model = User::model()->findByPk(1);

        $webUser->setModel($model);

        $this->assertSame($model, $webUser->getModel());
        $this->assertEquals($model->id, $webUser->getId());
    }

    /**
     * @depends testGetModel
     */
    public function testSyncModel(): void
    {
        Yii::app()->session->open();

        $_SESSION['__id'] = 1;

        $webUser = $this->createWebUser([
            'autoSyncModel' => false,
            'attributeToStateMap' => [
                'username' => '__name',
                'email' => 'email',
            ],
        ]);

        $webUser->syncModel();

        $this->assertNotEmpty($webUser->getModel());

        $this->assertEquals('active-user', $webUser->getName());
        $this->assertEquals('active-user@example.test', $webUser->getState('email'));

        $_SESSION['__id'] = 99;

        $webUser->syncModel();

        $this->assertNull($webUser->getModel());
        $this->assertNull($webUser->getId());
    }

    /**
     * @depends testSyncModel
     */
    public function testAutoSyncModel(): void
    {
        Yii::app()->session->open();

        $_SESSION['__id'] = 99;

        $webUser = $this->createWebUser([
            'autoSyncModel' => true,
        ]);

        $this->assertNull($webUser->getModel());
        $this->assertNull($webUser->getId());
    }

    /**
     * @depends testGetModel
     */
    public function testModelFindCriteria(): void
    {
        $webUser = $this->createWebUser([
            'autoSyncModel' => false,
            'modelFindCriteria' => [
                'condition' => 'status = :status',
                'params' => [
                    'status' => User::STATUS_ACTIVE,
                ],
            ],
        ]);

        $webUser->setId(1);
        $model = $webUser->getModel();

        $this->assertNotEmpty($model);

        $webUser->setId(2);
        $model = $webUser->getModel();

        $this->assertEmpty($model);
    }
}