<?php

use \console\components\Migration;

class tbl_config extends Migration
{
    protected $indexKeys = ['key', 'type'];


    public function init()
    {
        parent::init();
        $configurator = \Yii::$app->config;
        $this->tableName = $configurator::tableName();
        $this->columns = [
            'id'                        => 'SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'key'                       => 'VARCHAR(100) NOT NULL',
            'value'                     => 'VARCHAR(255) NOT NULL',
            'type'                      => 'TINYINT(4) UNSIGNED NOT NULL DEFAULT '.$configurator::TYPE_BOOLEAN,
            self::COLUMN_CREATED        => 'DATETIME NOT NULL',
            self::COLUMN_UPDATED        => 'DATETIME DEFAULT NULL',
        ];
    }
}
