<?php

namespace alviden\models;

use Yii;

/**
 * This is the model class for table "{{%searchhash}}".
 *
 * @property int $id
 * @property string $title
 * @property string $hash
 *
 */
class SearchHash extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%searchhash}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['hash', 'name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'hash' => 'Hash',
        ];
    }
}
