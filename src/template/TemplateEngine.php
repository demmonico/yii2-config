<?php
/**
 * @author: dep
 * Date: 08.09.16
 */

namespace demmonico\template;

use demmonico\helpers\FileHelper;
use demmonico\config\core\Configurator as BaseConfigurator;


/**
 * Component TemplateEngine works with app templates
 *
 * @property string key
 * @property string value
 *
 * @property string created
 * @property string updated
 *
 * @use
 * Get template with replaced occurrences which can be overwritten by admin
 *      Yii::$app->config->get('key', ['param1' => 'value1']);
 *
 * @use
 * At config file can set:
 * [
 *      'template' => [
 *          'class' => 'demmonico\template\TemplateEngine',
 *          'tableName' => 'tbl_template',
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
 * @see [demo/tbl_template.php]
 */
class TemplateEngine extends BaseConfigurator
{

    const EVENT_CLASS           = 'MissingTemplateEvent';
    const EVENT_NAME            = 'Configurator.MissingTemplate';


    /**
     * @inheritdoc
     */
    protected static $_tableName = '{{%template}}';

    /**
     * @inheritdoc
     */
    protected static $_handler = [
        'class' => 'MissingTemplateHandler',
    ];

    /**
     * Name of folder which should contents template source file.
     * @var string
     */
    public $templateFolder = '@common/template';

    /**
     * Extension of template source file.
     * @var string
     */
    public $templateExt = 'tmpl';

    /**
     * @var array
     */
    private $_templates;



    /**
     * Get template with replaced matches if exists
     * Order source: DB + [templateFolder], \common\mail\$file
     * @param $key
     * @param array $matches
     * @return string
     * @throws \Exception
     */
    public function get($key, array $matches=[])
    {
        // get template
        $template = $this->loadTemplate($key);
        if (!$template){
            $file = FileHelper::alias2path($this->templateFolder).$key.'.'.$this->templateExt;
            if (is_file($file) && touch($file) && $template = file_get_contents($file)){
                $this->throwMissingEvent($key, ['template' => $template]);
            } else {
                $this->throwException($key);
            }
        }

        // parse template
        if (!empty($matches)){
            $arr = [];
            foreach ($matches as $k=>$v){
                $arr['{{'.$k.'}}'] = $v;
            }
            $template = strtr($template, $arr);
        }

        return $template;
    }

    /**
     * Returns template from class cache or DB
     * @param $key
     * @return string
     */
    protected function loadTemplate($key)
    {
        if (!isset($this->_templates[$key])){
            $template = static::find()->select(['value'])->andWhere(['key'=>$key])->asArray()->one();
            $this->_templates[$key] = $template ?: null;
        }
        return $this->_templates[$key];
    }

}





use demmonico\config\core\MissingEvent;

class MissingTemplateEvent extends MissingEvent
{
    public $template;
}