<?php /**
 * @author: dep
 * Date: 05.09.16
 */

namespace demmonico\config\admin\actions;

use demmonico\config\core\Configurator;
use yii\base\Action;
use yii\web\ServerErrorHttpException;


abstract class ImportBaseAction extends Action
{
    /**
     * @var string Custom layout for action. Can be overwritten
     */
    public $layout;
    /**
     * @var string
     */
    public $modelClass;
    /**
     * @var Configurator
     */
    protected $model;



    public function init()
    {
        // check $modelClass for empty
        if (empty($this->modelClass)){
            // try to get modelClass from controller
            if (isset($this->controller->modelClass) && !empty($this->controller->modelClass))
                $this->modelClass = $this->controller->modelClass;
            else
                throw new ServerErrorHttpException('Invalid model class');
        }

        // check $modelClass for instance
        $this->model = \Yii::createObject($this->modelClass);
        if (!$this->model instanceof Configurator){
            throw new ServerErrorHttpException('Invalid instance of model class');
        }

        // set layout
        if (!empty($this->layout))
            $this->controller->layout = $this->layout;
    }

}