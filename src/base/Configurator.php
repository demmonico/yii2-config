<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\config\base;

use demmonico\models\ActiveRecord;
use yii\helpers\ArrayHelper;


/**
 * Abstract component class Configurator for automatic work with configs
 */
abstract class Configurator extends ActiveRecord
{
    /**
     * TODO overwrite this in inheritances
     */
    const EVENT_CLASS           = ''; //'MissingEvent';
    /**
     * TODO overwrite this in inheritances
     */
    const EVENT_NAME            = ''; //'Config.MissingVariable';


    /**
     * Table name for config storage.
     * Should be overwritten at inheritances
     * @var string
     */
    protected static $_tableName;
    /**
     * Parametrize property of static::$_tableName
     * Can be set from config file
     * @var string
     */
    public $tableName;

    /**
     * Array of event Handler properties.
     * Can be overwritten
     * @var array
     *
     * @use
     * [
     *      'class' => 'MissingHandler',
     * ]
     */
    protected static $handlerConfig = [];
    protected static $_handlerConfig;
    /**
     * Parametrize property of static::$handler
     * Can be set from config file
     * @var array
     */
    public $handler;

    /**
     * Path or alias to separate file with default config file.
     * @var string
     * @use
     * static::$_defaultConfigFile = '@common/data/default';
     * or
     * static::$_defaultConfigFile = '@common/data/default.php';
     */
    protected static $_defaultConfigFile;
    /**
     * Parametrize property of static::$_defaultConfigFile
     * Can be set from config file
     * @var array
     */
    public $defaultConfigFile;



    /**
     * @return string
     */
    public static function tableName()
    {
        return static::$_tableName;
    }

    /**
     * Returns namespace
     * @param $currentClass
     * @return string
     */
    public static function getNamespace($currentClass)
    {
        $reflector = new \ReflectionClass($currentClass);
        return $reflector->getNamespaceName();
    }

    /**
     * Returns full classname (with namespace)
     * @param $className
     * @return string
     */
    public static function getNamespacedClass($className)
    {
        return static::getNamespace( get_called_class() ) . '\\' . $className;
    }

    /**
     * Returns full classname (with namespace) of handler
     * @return string
     * @throws \Exception
     */
    public static function getHandlerClass()
    {
        $config = static::getHandlerConfig();
        return static::getNamespace( get_called_class() ) . '\\' . $config['class'];
    }

    /**
     * Get config array for init Handler
     * @return mixed
     */
    public static function getHandlerConfig()
    {
        if (is_null(static::$_handlerConfig)){
            static::$_handlerConfig = array_merge([
                'class' => 'MissingHandler',
                'config' => [],
            ], static::$handlerConfig);
        }
        return static::$_handlerConfig;
    }



    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // reset static::$tableName property
        if (isset($this->tableName))
            static::$_tableName = $this->tableName;

        // reset static::$handler property
        if (isset($this->handler) && is_array($this->handler))
            static::$handlerConfig = array_merge(static::$handlerConfig, $this->handler);

        // reset static::$_defaultConfigFile property
        if (isset($this->defaultConfigFile))
            static::$_defaultConfigFile = $this->defaultConfigFile;


        // init events
        foreach ($this->events() as $event => $handlers) {
            if (is_array($handlers)) foreach($handlers as $handler){
                if(ArrayHelper::isAssociative($handler)){
                    $this->on($event, $handler['handler'], ArrayHelper::getValue($handler, 'data'));
                } else {
                    $this->on($event, is_string($handler) ? [$this, $handler] : $handler);
                }
            }
        }
    }

    /**
     * Set array of events
     * @return array
     */
    public function events()
    {
        return [
            static::EVENT_NAME => [[static::getHandlerClass(), 'collect']],
        ];
    }



    /**
     * @param $key
     * @param array $attributes
     */
    protected function throwMissingEvent($key, $attributes=[])
    {
        $handlerClass = static::getHandlerClass();
        $eventName  = static::EVENT_NAME;
        $eventClass = static::getNamespacedClass( static::EVENT_CLASS );
        if (!empty($eventClass))
            $eventClass = call_user_func($eventClass.'::className');

        if (!empty($eventClass) && !empty($handlerClass) && !empty($eventName)){
            $event  = \Yii::createObject(['class'=>$eventClass, 'key'=>$key, 'attributes'=>$attributes]);
            $this->trigger($eventName, $event);
        }
    }

    /**
     * @param $key
     * @throws \Exception
     */
    protected function throwException($key)
    {
        throw new \Exception('Empty default configuration for key '.$key);
    }
}



use yii\base\Event;

class MissingEvent extends Event
{
    public $key;
    public $attributes;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        foreach($this->attributes as $k=>$v)
            if (property_exists(get_called_class(), $k))
                $this->{$k} = $v;
    }

}