<?php /**
 * @author: dep
 * Date: 05.09.16
 */

namespace demmonico\config\admin\actions;

use yii\helpers\Html;


/**
 * Class ImportDefaultAction import default values of params
 * @author: dep
 * @package demmonico\config\admin\actions
 */
class ImportDefaultAction extends ImportBaseAction
{
    public function run()
    {
        $session = \Yii::$app->getSession();

        $r = call_user_func($this->modelClass.'::importDefault');
        if ($r['success'])
            $session->setFlash('success', 'There are '.$r['success'].' variables were imported successfully. Check them types!');
        if (!empty($r['errors']))
            $session->setFlash('error', Html::errorSummary($r['errors'], ['header'=>'Import errors: ']));

        return $this->controller->redirect(['index']);
    }
}