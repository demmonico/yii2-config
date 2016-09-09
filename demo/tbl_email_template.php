<?php

use \console\components\Migration;

class tbl_email_template extends Migration
{
    protected $indexKeys = ['key'];


    public function init()
    {
        parent::init();
        $this->tableName = \Yii::$app->email->tableName();
        $this->columns = [
            'id'                        => 'SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'key'                       => 'VARCHAR(255) NOT NULL',
            'subject'                   => 'VARCHAR(255) NOT NULL',
            'body'                      => 'TEXT NOT NULL',
            'layout'                    => 'TEXT DEFAULT NULL',
            self::COLUMN_CREATED        => 'DATETIME NOT NULL',
            self::COLUMN_UPDATED        => 'DATETIME NOT NULL',
        ];
    }
}
