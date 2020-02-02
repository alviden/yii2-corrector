<?php

use yii\db\Migration;

/**
 * Class m200202_114050_searchhash
 */
class m200202_114050_searchhash extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%searchhash}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'hash' => $this->binary(16)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%searchhash}}');
    }

}
