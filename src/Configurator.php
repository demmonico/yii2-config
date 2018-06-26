<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\config;

use demmonico\reflection\ConstantTrait;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;
use demmonico\config\core\Configurator as BaseConfigurator;

/**
 * Component Configurator works with app configs
 *
 * @property string key
 * @property string value
 * @property string type
 *
 * @property string created
 * @property string updated
 *
 * @use Yii::$app->config->app('name');                     // get app params overwritten by admin
 * @use Yii::$app->config->get('name');                     // get app configs overwritten by admin
 * @use Yii::$app->config->get('name', 'defaultValue');     // get app configs with local default value overwritten by admin
 * @use At config file can pre-configuring other components:
 * [
 *      'sms' => [
 *          'class' => 'demmonico\sms\Sender',
 *          'senderNumber' => [
 *              'component' => 'config',
 *              'sms.senderNumber' => 'AppName',
 *          ],
 *      ],
 * ]
 *
 * @use At config file can set:
 * [
 *      'config' => [
 *          'class' => 'demmonico\config\Configurator',
 *          'tableName' => 'tbl_config',
 *          'defaultConfigFile' => '@common/data/default.php',
 *          'handler' => [
 *              'class' => 'testHandler',
 *              'config'=> [
 *                  'fileStorage' => 'missing_configs',
 *                  'folderStorage' => '@common/data/',
 *              ],
 *          ],
 *      ],
 * ]
 * Folder specified as `folderStorage` should be exist. Here `fileStorage` file will be created if some config params will be exist.
 * Recommended to create folder previously with `.gitkeep` file inside.
 *
 * @see [demo/tbl_config.php]
 */
class Configurator extends BaseConfigurator implements BootstrapInterface
{
    use ConstantTrait;


    const EVENT_CLASS           = 'MissingConfigEvent';
    const EVENT_NAME            = 'Configurator.MissingConfig';

    // types of config value
    const TYPE_BOOLEAN  = 1;
    const TYPE_INTEGER  = 2;
    const TYPE_NUMBER   = 3;
    const TYPE_STRING   = 5;
    const TYPE_EMAIL    = 7;
    const TYPE_URL      = 8;
    const TYPE_DATE     = 10;
    const TYPE_DATETIME = 11;
    const TYPE_TIME     = 12;
    const TYPE_CURRENCY = 15;

    const CACHE_NAME = 'Configurator.configs';


    /**
     * @inheritdoc
     */
    protected static $_tableName = '{{%config}}';

    /**
     * @inheritdoc
     */
    protected static $_handler = [
        'class' => 'MissingConfigHandler',
    ];

    /**
     * Parametrize property of [_defaultConfigFile]
     * Can be set from config file
     * @var array
     */
    public $defaultConfigFile;
    /**
     * Path or alias to separate file with default config file.
     * @var string
     * @use
     * [_defaultConfigFile] = '@common/data/default';
     * or
     * [_defaultConfigFile] = '@common/data/default.php';
     */
    protected static $_defaultConfigFile;


    /**
     * @var array
     */
    public $bootstrap = [];
    /**
     * @var array
     */
    private $_configs;
    /**
     * @var array
     */
    private $_defaults;



    /**
     * Load default configs array from file
     * @return array
     */
    public static function loadDefaultConfigs()
    {
        if (isset(self::$_defaultConfigFile)){
            $file = \Yii::getAlias(self::$_defaultConfigFile);
            // if extension absent - add
            if ('' === pathinfo($file, PATHINFO_EXTENSION)){
                $file .= '.php';
            }
        }
        return (isset($file) && is_file($file)) ? require($file) : [];
    }



    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // reset [_defaultConfigFile] property
        if (isset($this->defaultConfigFile))
            self::$_defaultConfigFile = $this->defaultConfigFile;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->decodeAttribute();
    }

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap( $app )
    {
        if (!empty($this->bootstrap)) {
            $components = $app->getComponents();
            foreach($this->bootstrap as $component=>$config){
                // get name
                if (isset($components[$component], $components[$component]['class']))
                    $component = $components[$component]['class'];
                // collect config
                foreach($config as $k=>$v){
                    $config[$k] = is_array($v) ? $this->get($v[0], $v[1]) : $this->get($v);
                }
                \Yii::$container->set($component, $config);
            }
        }
    }



    /**
     * Load defaults array
     * Order defaults include: 'Yii::$app->params' merge with FILE_DEFAULT_CONFIG
     * @param null $key
     * @return array
     * @throws \Exception
     */
    public function defaults($key=null)
    {
        if (is_null($this->_defaults)){
            $this->_defaults = ArrayHelper::merge(\Yii::$app->params, self::loadDefaultConfigs());
        }

        if (!is_null($key)){
            if (isset($this->_defaults[$key]))
                return $this->_defaults[$key];
            else
                $this->throwException($key);
        }
        return $this->_defaults;
    }

    /**
     * Get config key
     * Order source: object variable, cache, DB, custom param $default, common defaults array, Yii::$app->params array
     * @param $key
     * @param null $default
     * @return mixed
     * @throws \Exception
     */
    public function get($key, $default=null)
    {
        $configs = $this->loadConfig();
        if(!isset($configs[$key])){
            $this->_configs[$key] = is_null($default) ? $this->defaults($key) : $default;
            $this->throwMissingEvent($key, ['value' => $this->_configs[$key]]);
        }
        return $this->_configs[$key];
    }

    /**
     * Get configs array by key mask
     * Default values does not appears. Only specified at DB.
     * Order source: object variable, cache, DB
     * @param $keyCondition
     * @return array
     */
    public function getLike($keyCondition)
    {
        $r = [];
        $configs = $this->loadConfig();
        if (!empty($configs)) foreach($configs as $k=>$v){
            if (false !== strpos($k, $keyCondition)){
                $r[$k] = $v;
            }
        }
        return $r;
    }

    /**
     * Get Yii::$app config key
     * Order source: object variable, cache, DB, Yii::$app->$key
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function app($key)
    {
        $configs = $this->loadConfig();
        $namespacedKey = 'appconfig.'.$key;
        if(!isset($configs[$namespacedKey])){
            if (isset(\Yii::$app->$key)){
                $this->_configs[$namespacedKey] = \Yii::$app->$key;
                $this->throwMissingEvent($namespacedKey, ['value' => $this->_configs[$namespacedKey]]);
            } else {
                $this->throwException($namespacedKey);
            }
        }
        return $this->_configs[$namespacedKey];
    }



    /**
     * Returns configs array
     * @return array|mixed
     */
    protected function loadConfig()
    {
        if (is_null($this->_configs)){
            $this->_configs = \Yii::$app->cache->get(self::CACHE_NAME);
            if (false === $this->_configs){
                $this->_configs = ArrayHelper::map( static::find()->select(['key', 'value'])->all(), 'key', 'value' );
                \Yii::$app->cache->set(self::CACHE_NAME, $this->_configs, 10000);
            }
        }
        return $this->_configs;
    }

}





use demmonico\config\core\MissingEvent;
use demmonico\reflection\ReflectionHelper;

class MissingConfigEvent extends MissingEvent
{
    public $value;
    public $type;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->type = ReflectionHelper::detectVarType($this->value, Configurator::getType(), Configurator::TYPE_STRING);
    }
}