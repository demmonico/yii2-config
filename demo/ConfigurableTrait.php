<?php
/**
 * @author: dep
 * 06.09.16
 */

//namespace namespaceOfCurrentComponent;

/**
 * Trait ConfigurableTrait implements Yii2 Configurable interface and
 * automatic loading config params from Yii2 config component ( @see https://github.com/demmonico/yii2-config )
 * @author: dep
 * @package demmonico\sms
 *
 * @use
 * In class which should implements Yii2 Configurable interface and/or Yii2 config component load:
 *
 * class SomeClass
 * {
 *      // include this trait
 *      use ConfigurableTrait;
 *
 *      function __construct($configs = [])
 *      {
 *          // apply configurable
 *          $this->applyConfigs($configs);
 *          ...
 *      }
 *
 *      ...
 * }
 *
 * Then in Yii2 config file:
 * ...
 * 'components => [
 *      ...
 *      'someName' => [
 *          'class' => 'SomeClass_with_namespace',
 *          'param1' => 2,                          // default configurable case
 *          'param2' => '2',                        // default configurable case
 *          'param3' => [1, '2', 3 => 'test'],      // default configurable case
 *          // Yii2 config component call for param with 'someParamName' name
 *          'param4' => ['component' => 'config', 'someParamName'],
 *          // Yii2 config component call for param with 'someParamName' name, default value will be - 2
 *          'param4' => ['component' => 'config', 'someParamName' => 2],
 *      ],
 *      ...
 * ],
 * ...
 */
trait ConfigurableTrait
{
    /**
     * Applies configs array
     * @param array $configs
     */
    protected function applyConfigs(array $configs = [])
    {
        $className = get_called_class();
        if (is_array($configs)) foreach ($configs as $k=>$v){
            if (property_exists($className, $k)){

                // if config passes in yii2 config component format
                if (is_array($v) && sizeof($v) == 2 && isset($v['component'])
                    // and if exists pointed yii2 config component
                    && isset(\Yii::$app->{$v['component']})
                ) {
                    $componentName = $v['component'];
                    unset($v['component']);

                    // get value from yii2 config component without default value
                    $key = key($v);
                    if (is_int( $key )){
                        $this->$k = \Yii::$app->{$componentName}->get( current($v) );

                    // get value from yii2 config component with default value
                    } else {
                        $this->$k = \Yii::$app->{$componentName}->get($key, current($v));
                    }


                // default configurable behavior
                } else {
                    $this->$k = $v;
                }
            }
        }
    }
}