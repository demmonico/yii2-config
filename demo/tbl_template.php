<?php

use \console\components\Migration;

class tbl_template extends Migration
{
    protected $indexKeys = ['key'];


    public function init()
    {
        parent::init();
        $templateEngine = \Yii::$app->template;
        $this->tableName = $templateEngine::tableName();
        $this->columns = [
            'id'                        => 'SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'key'                       => 'VARCHAR(100) NOT NULL',
            'value'                     => 'TEXT NOT NULL',
            self::COLUMN_CREATED        => 'DATETIME NOT NULL',
            self::COLUMN_UPDATED        => 'DATETIME DEFAULT NULL',
        ];
    }
}
