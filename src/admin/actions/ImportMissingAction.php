<?php /**
 * @author: dep
 * Date: 05.09.16
 */

namespace demmonico\config\admin\actions;

use yii\helpers\Html;


/**
 * Class ImportMissingAction imports missing params
 * @author: dep
 * @package demmonico\config\admin\actions
 */
class ImportMissingAction extends ImportBaseAction
{
    public function run()
    {
        $session = \Yii::$app->getSession();

        // clear all missing
        if ('clear-all' === \Yii::$app->request->get('mode')){
            if (call_user_func($this->modelClass.'::removeMissingAll')){
                $session->setFlash('success', 'Missing configs were cleared successfully.');
            } else {
                $session->setFlash('error', 'Internal error occurred while clearing missing configs!');
            }

        // import missing
        } else {
            $r = call_user_func($this->modelClass.'::importMissing');
            if ($r['success'])
                $session->setFlash('success', 'There are '.$r['success'].' configs were imported successfully. Check them!');
            if (!empty($r['errors']))
                $session->setFlash('error', Html::errorSummary($r['errors'], ['header'=>'Import errors: ']));
        }

        return $this->controller->redirect(['index']);
    }
}