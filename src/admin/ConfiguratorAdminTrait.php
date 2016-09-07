<?php /**
 * @author: dep
 * Date: 27.01.16
 */

namespace demmonico\config\admin;

use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;


/**
 * Common use methods for admin configs
 */
trait ConfiguratorAdminTrait
{
    /**
     * Returns missing count
     * @return mixed
     */
    public static function getMissingCount()
    {
        return call_user_func(static::getHandlerClass().'::count');
    }

    /**
     * Returns missing array
     * @return array
     */
    public static function getMissingArray()
    {
        return call_user_func(static::getHandlerClass().'::getMissingArray');
    }



    /**
     * Returns result of import operation missing
     * @return array
     */
    public static function importMissing()
    {
        $success = 0;
        $errors = [];

        $arr = self::getMissingArray();
        foreach($arr as $i){
            $model = \Yii::createObject( get_called_class() );
            $model->setAttributes($i);
            if ($model->save()) {
                self::removeMissing($model->key);
                $success++;
            } else {
                $errors[] = $model;
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }

    /**
     * Remove resolved missing from missing array
     * @param $key
     * @return mixed
     */
    public static function removeMissing($key)
    {
        return call_user_func(static::getHandlerClass().'::resolve', $key);
    }

    /**
     * Remove all from missing array
     * @return mixed
     */
    public static function removeMissingAll()
    {
        return call_user_func(static::getHandlerClass().'::clearAll');
    }



    /**
     * Returns array of default values
     * @return array
     */
    public static function importDefault()
    {
        $success = 0;
        $errors = [];

        $arr = self::getMissingArray();
        foreach($arr as $k=>$v){
            $model = static::findOne(['key'=>$k]);
            if (!$model)
                $model = \Yii::createObject( get_called_class() );
            $model->setAttributes($v);
            if ($model->save()) {
                $success++;
            } else {
                $errors[] = $model;
            }
        }

        return ['success' => $success, 'errors' => $errors];
    }



    /**
     * Set flash to show import btn
     * @param $importUrl
     */
    public static function setFlash($importUrl)
    {
        if ($missings = static::getMissingCount()){
            $clearUrl = is_array($importUrl) ? array_merge($importUrl, ['mode'=>'clear-all']) : $importUrl.'?mode=clear-all';
            \Yii::$app->getSession()->setFlash('warning',
                '<div class="row"><div class="col-xs-7">There are '.$missings.' missed configs, which you have to import.</div>'.
                self::renderBtn('Import missed', $importUrl, ['class'=>'btn btn-info']).
                self::renderBtn('Clear missed', $clearUrl, ['class'=>'btn btn-danger']).
                '</div>'
            );
        }
    }

    /**
     * Renders btn html code
     * @param null $label
     * @param null $url
     * @param array $linkParams
     * @param array $formParams
     * @return string
     */
    public static function renderBtn($label=null, $url=null, $linkParams=[], $formParams=[])
    {
        if (is_null($label))
            $label = 'Create new';
        if (is_null($url))
            $url = ['create'];

        // link
        if (isset($linkParams['linkContainerWidth'])){
            $linkContainerWidth = $linkParams['linkContainerWidth'];
            unset($linkParams['linkContainerWidth']);
        } else {
            $linkContainerWidth = 2;
        }
        $linkParams = ArrayHelper::merge(['class' => 'btn btn-success', 'data-pjax'=>0], $linkParams);
        if (isset($formParams['template'])){
            $template = strtr($formParams['template'], ['{{link}}'=>Html::a($label, $url, $linkParams)]);
            unset($formParams['template']);
        } else {
            $template = Html::a($label, $url, $linkParams);
        }

        $formParams = ArrayHelper::merge(['onChange'=>'this.submit();','class'=>'form-inline'], $formParams);
        return '<div class="col-xs-'.$linkContainerWidth.'">'.Html::tag('form', $template, $formParams).'</div>';
    }

}