<?php
/**
 * @author: dep
 * Date: 09.02.16
 */

namespace demmonico\config\core;

use demmonico\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;


abstract class MissingHandler
{
    /**
     * Name of file where will be stored missing configs
     * Can be overwritten
     */
    public static $fileStorage = 'missing_configs.php';
    /**
     * Name of folder which should contents file storage.
     * TODO Folder must exists!!! and should be under git ignore
     * @var string
     */
    public static $folderStorage = '@common/data/';
    /**
     * Parametrize property of static::$fileStorage and static::$folderStorage
     * Can be set from config file and passed throw Configurator
     * @var string
     */
    protected static $config;


    /**
     * Array of variables sorted by related handlers
     * @var array
     * @use [
     *      'MissingHandler1' => [...]  // variables
     *      'MissingHandler2' => [...]  // variables
     * ]
     */
    protected static $variables = [];
    /**
     * Array of flags showing ability to export missings by handlers
     * @var array
     * @use [
     *      'MissingHandler1' => true
     *      'MissingHandler2' => true
     * ]
     */
    protected static $isSetExport = [];



// use for configurator

    /**
     * Collect missings. Trigger event for handler
     * @param MissingEvent $event
     */
    public static function collect(MissingEvent $event)
    {
        $params = static::getMissingArray();
        if(!isset($params[$event->key])){
            static::addMissing($event);
            $className = get_called_class();
            if ( !isset( static::$isSetExport[ $className ] ) ){
                \Yii::$app->on(\yii\base\Application::EVENT_AFTER_REQUEST, [$className, 'export']);
                static::$isSetExport[ $className ] = true;
            }
        }
    }

    /**
     * Returns size of config array
     * @return int
     */
    public static function count()
    {
        return sizeof(static::getMissingArray());
    }

    /**
     * Export missings to file
     */
    public static function export()
    {
        $params = static::getMissingArray();
        $file = static::getStoragePath();
        if($params){
            $isNew = !is_file($file);
            if ($isNew){
                $dir = dirname($file);
                if (!is_dir($dir))
                    FileHelper::mkdir($dir);
            }
            file_put_contents($file, "<?php\nreturn " . VarDumper::export($params) . ";\n", LOCK_EX);
            if ($isNew)
                FileHelper::chmod($file);
        } else {
            FileHelper::unlink($file);
        }
    }


// use for admin

    /**
     * Remove resolved missing from missing array
     * @param $key
     */
    public static function resolve($key)
    {
        $variables = static::getMissingArray();
        if (array_key_exists($key, $variables)){
            unset($variables[$key]);
            static::setMissingArray($variables);
        }
        static::export();
    }

    /**
     * @return bool
     */
    public static function clearAll()
    {
        return FileHelper::unlink( static::getStoragePath() );
    }




    /**
     * Returns full path to file of storage missing
     * @return string
     */
    public static function getStoragePath()
    {
        $dynamicConfig = static::$config;
        $folder = FileHelper::alias2path( isset($dynamicConfig['folderStorage']) ? $dynamicConfig['folderStorage'] : static::$folderStorage );
        $file = isset($dynamicConfig['fileStorage']) ? $dynamicConfig['fileStorage'] : static::$fileStorage;
        return $folder.$file.('' === pathinfo($file, PATHINFO_EXTENSION) ? '.php' : '');
    }

    /**
     * Returns missing array
     * @param bool|false $force
     * @return mixed
     */
    public static function getMissingArray($force = false)
    {
        $className = get_called_class();
        if ( $force || !isset( static::$variables[ $className ] ) ){
            $file = static::getStoragePath();
            static::setMissingArray(is_file($file) ? require($file) : []);
        }
        return static::$variables[ $className ];
    }

    /**
     * Sets missing array
     * @param array $attributes
     */
    protected static function setMissingArray($attributes)
    {
        static::$variables[ get_called_class() ] = $attributes;
    }

    /**
     * Appends to missing array
     * @param MissingEvent $event
     */
    protected static function addMissing($event)
    {
        if ($event instanceof MissingEvent){
            $className = get_called_class();

            // store params
            static::$variables[ $className ] = ArrayHelper::merge(
                isset(static::$variables[ $className ]) ? static::$variables[ $className ] : [],
                static::parseAttributes($event)
            );

            // store handler config from configurator
            if ($event->sender && $event->sender instanceof Configurator){
                $handlerParams = call_user_func([get_class($event->sender), 'getHandlerConfig']);
                if (isset($handlerParams, $handlerParams['config']))
                    static::$config = $handlerParams['config'];
            }
        }
    }



    /**
     * Prepare attributes array for missing event logger
     * Need to be overwritten
     * @param MissingEvent $event
     * @use inside write e.g.
     * return [ $event->key => [
     *      'key'   => $event->key,
     *      'value' => $event->value,
     *      'type'  => $event->type,
     * ] ];
     */
    public static function parseAttributes(MissingEvent $event){}

}