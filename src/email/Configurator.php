<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\email;

use demmonico\helpers\FileHelper;
use demmonico\config\core\Configurator as BaseConfigurator;
use yii\helpers\Html;


/**
 * Component TemplateEngine works with app templates
 *
 * @property string key
 * @property string subject
 * @property string body
 * @property string layout
 *
 * @property string created
 * @property string updated
 *
 * @use
 * Use this with [Mailer]
 *
 * @see [demo/tbl_email.php]
 */
class Configurator extends BaseConfigurator
{
    const EVENT_CLASS           = 'MissingEmailEvent';
    const EVENT_NAME            = 'Configurator.MissingEmail';


    /**
     * @inheritdoc
     */
    protected static $_tableName = '{{%email_template}}';

    /**
     * @inheritdoc
     */
    protected static $_handler = [
        'class' => 'MissingEmailHandler',
    ];

    public static $folderTemplate = '@common/data/mail_templates';

    /**
     * @var array
     */
    private $_emails;



    /**
     * Get email subject
     * Order source: DB, \common\mail\$file
     * @param $key
     * @param $default
     * @return string
     */
    public function getSubject($key, $default)
    {
        $email = $this->loadEmail($key);
        if(!isset($email['subject'])){
            $this->_emails[$key]['subject'] = $default;
            $this->throwMissingEvent($key, $this->_emails[$key]);
        }
        return $this->_emails[$key]['subject'];
    }

    /**
     * Get email body
     * Order source: DB + static::$folderTemplate, \common\mail\$file
     * @param $key
     * @return string
     */
    public function getTemplatePath($key)
    {
        $email = $this->loadEmail($key);
        if (!isset($email['body'])){
            $this->_emails[$key]['body'] = null;
            $this->throwMissingEvent($key, $this->_emails[$key]);
        } elseif ( is_file( FileHelper::alias2path(static::$folderTemplate).$key ) ){
            return static::$folderTemplate;
        }
        return null;
    }

    /**
     * Get email layout
     * Order source: DB + static::$folderTemplate, \common\mail\$file
     * @param $key
     * @return string
     */
    public function getLayoutPath($key)
    {
        $email = $this->loadEmail($key);
        if (!isset($email['layout'])){
            $this->_emails[$key]['layout'] = null;
            $this->throwMissingEvent($key, $this->_emails[$key]);
        } elseif (!empty($email['layout'])) {
            $file = FileHelper::alias2path(static::$folderTemplate . '/layouts') .$key;
            if ( is_file( $file ))
                return static::$folderTemplate . '/layouts/' . $key;
        }
        return null;
    }


    /**
     * Returns email from class cache or DB
     * @param $key
     * @return string
     */
    protected function loadEmail($key)
    {
        if (!isset($this->_emails[$key])){
            $email = static::find()->select(['key','subject','body','layout'])->andWhere(['key'=>$key])->asArray()->one();
            if ($email){
                $email['subject'] = Html::decode($email['subject']);
                $this->_emails[$key] = $email;
            } else {
                $this->_emails[$key] = [];
            }
        }
        return $this->_emails[$key];
    }

}





use demmonico\config\core\MissingEvent;

class MissingEmailEvent extends MissingEvent
{
    public $subject;
}