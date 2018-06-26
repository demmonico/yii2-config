<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\config\core;

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
     * Parametrize property of static::$handlerActiveRecord
     * Can be set from config file
     * @var array
     */
    public $handler;
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
    protected static $_handler;
    private static $_handlerConfig;



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
        $className = get_called_class();
        if (!isset(self::$_handlerConfig[$className])){
            self::$_handlerConfig[$className] = array_merge([
                'class' => 'MissingHandler',
                'config' => [],
            ], !empty(static::$_handler) && is_array(static::$_handler) ? static::$_handler : []);
        }
        return self::$_handlerConfig[$className];
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
        if (isset($this->handler) && is_array($this->handler)){
            static::$_handler = array_merge(
                !empty(static::$_handler) && is_array(static::$_handler) ? static::$_handler : [],
                $this->handler
            );
        }


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
     * Prepare event, handler and throw missing event
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
     * Throw exception
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