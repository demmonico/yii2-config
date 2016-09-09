<?php
/**
 * @author: dep
 * Date: 18.02.16
 */

namespace demmonico\email;

use yii\base\Configurable;
use yii\helpers\StringHelper;


/**
 * Class Mailer help to use [Swiftmailer] component with [Configurator] email templates
 *
 * If param [redirectEmail] will be configured then all emails will be redirected to this email (field [to] will be ignored)
 * If param [isTransferEnabled] is set to [false] then mailer's option [useFileTransport] will be set to [true] instead of real transfer mail
 *
 * @use
 * \Yii::$app->setTemplate('email_key')->setTo('email')->send();
 * or
 * \Yii::$app->email
 *      ->setTemplate('test-html.php')
 *      ->setTo('demmonico@gmail.com')
 *      ->setFrom('admin@localhost')
 *      ->setSubject('Your account on ' . $appName)
 *      ->setParams(['user' => $this, 'appName' => $appName])
 *      ->send();
 */
class Mailer implements Configurable
{
    // include this trait
    use ConfigurableTrait;


    /**
     * Parametrize property of Configurator->tableName
     * Can be set from config file
     * @var string
     */
    public $tableName;

    public $mailerComponentName = 'mailer';
    public $mailerComponentViewPath = '@common/mail';
    public $configComponentName = 'config';

    // email params
    public $template;
    public $params = [];

    // email fields
    public $to;
    public $from;
    public $fromDefaultEmail;
    public $fromDefaultEmailConfigParam = 'noreplyEmail';
    public $fromDefaultName;
    public $subject;

    /**
     * If this param is set then all emails will be redirected to this email (field $to will be ignored)
     * @var string
     * @use
     * 'components' => [
     *      // ...
     *      'email' => [
     *          'class' => 'demmonico\email\Mailer',
     *          'redirectEmail' => 'demmonico@gmail.com',
     *      ],
     * ],
     *
     * To set this param from config file dynamically with possibility to modifying it, try use Config component construction:
     * 'components' => [
     *      // ...
     *      'email' => [
     *          'class' => 'demmonico\email\Mailer',
     *          'redirectEmail' => [
     *              'component' => 'config',
     *              'email.debug.redirectEmail' => 'demmonico@gmail.com',
     *          ]
     *      ],
     * ],
     */
    public $redirectEmail;
    /**
     * If this param is set to [false] then mailer's option [useFileTransport] will be set to [true] instead of real transfer mail
     * @var bool
     * @use
     * 'components' => [
     *      // ...
     *      'email' => [
     *          'class' => 'demmonico\email\Mailer',
     *          'isTransferEnabled' => false,
     *      ],
     * ],
     *
     * To set this param from config file dynamically with possibility to modifying it, try use Config component construction:
     * 'components' => [
     *      // ...
     *      'email' => [
     *          'class' => 'demmonico\email\Mailer',
     *          'isTransferEnabled' => [
     *              'component' => 'config',
     *              'email.debug.isTransferEnabled' => false,
     *          ]
     *      ],
     * ],
     */
    public $isTransferEnabled = true;

    private $_emails = [];



    /**
     * Mailer constructor.
     * @param array $configs
     */
    public function __construct($configs = [])
    {
        // apply configurable
        $this->applyConfigs($configs);
    }

    /**
     * Prepare email params and build compose email of system mailer
     * @return mixed
     * @throws \Exception
     */
    public function send()
    {
        if (is_null($this->template) || is_null($this->to))
            throw new \Exception('Missing required params at '.StringHelper::basename(get_called_class()).' method');

        if (is_null($this->from)){
            if (is_null($this->fromDefaultEmail) && isset($this->fromDefaultEmailConfigParam))
                $this->fromDefaultEmail = \Yii::$app->{$this->configComponentName}->get($this->fromDefaultEmailConfigParam);
            if (is_null($this->fromDefaultName))
                $this->fromDefaultName = \Yii::$app->{$this->configComponentName}->app('name').' robot';
            $this->from = [$this->fromDefaultEmail => $this->fromDefaultName];
        }

        if (is_null($this->subject))
            $this->subject = \Yii::$app->{$this->configComponentName}->app('name').' email';
        $this->subject = $this->loadEmailConfig()->getSubject($this->template, $this->subject);


        // get mailer component
        $mailer = \Yii::$app->{$this->mailerComponentName};
        // check for file transport mode
        $mailer->useFileTransport = !$this->isTransferEnabled;

        // view path
        if ($viewPath = $this->loadEmailConfig()->getTemplatePath($this->template))
            $mailer->setViewPath($viewPath);

        // layout path
        if ($layoutPath = $this->loadEmailConfig()->getLayoutPath($this->template)){
            $mailer->htmlLayout = $layoutPath;
        } elseif ($viewPath) {
            $mailer->htmlLayout = $this->mailerComponentViewPath.DIRECTORY_SEPARATOR.$mailer->htmlLayout;
        }


        return $mailer->compose($this->template, $this->params)
            ->setFrom($this->from)
            ->setTo( $this->redirectEmail ?: $this->to)
            ->setSubject($this->subject)
            ->send();
    }


    /**
     * Load class Configurator instance
     * @return Configurator
     */
    protected function loadEmailConfig()
    {
        if (!isset($this->_emails[ $this->template ]))
            $this->_emails[$this->template] = new Configurator(['tableName' => $this->tableName]);
        return $this->_emails[$this->template];
    }



    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        if (!empty($template))
            $this->template = $template;
        return $this;
    }

    /**
     * @param string|array $to
     * @return $this
     */
    public function setTo($to)
    {
        if (!empty($to))
            $this->to = $to;
        return $this;
    }

    /**
     * @param string $from
     * @return $this
     */
    public function setFrom($from)
    {
        if (!empty($from))
            $this->from = $from;
        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        if (!empty($subject))
            $this->subject = $subject;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams($params)
    {
        if (!empty($params))
            $this->params = $params;
        return $this;
    }



    /**
     * Return Configurator's tableName
     * @return string
     */
    public function tableName()
    {
        $configurator = $this->loadEmailConfig();
        return $configurator::tableName();
    }

}