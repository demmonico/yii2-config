<?php /**
 * @author: dep
 * Date: 12.02.16
 */

namespace demmonico\config;

use demmonico\config\admin\ConfiguratorAdminTrait;
use demmonico\helpers\ReflectionHelper;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\validators\Validator;


/**
 * Extends class of Configurator for admin panel
 */
class ConfiguratorAdmin extends Configurator
{
    use ConfiguratorAdminTrait;

    public $frontendCacheComponent = 'cacheFrontend';
    public $backendCacheComponent = 'cache';


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
//            [['key','value'], 'trim'],
            [['key', 'type', 'value'], 'required', 'on' => self::SCENARIO_DEFAULT],

            ['key', 'unique', 'targetAttribute' => ['key'], 'on' => self::SCENARIO_DEFAULT, 'message'=> '{attribute} "{value}" already exists.'],
            ['key', 'string', 'max' => 32, 'on' => self::SCENARIO_DEFAULT],

            ['value', 'valueValidator', 'on' => self::SCENARIO_DEFAULT],
            ['value', 'string', 'max' => 255, 'on' => self::SCENARIO_DEFAULT, 'when' => function(){return $this->type==self::TYPE_STRING;}],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_SEARCH => ['key', 'value', 'type', 'created', 'updated']
        ]);
    }

    /**
     * @inheritdoc
     */
    public function search($params)
    {
        $query = static::find();
        $dataProvider = new ActiveDataProvider(['query' => $query]);
        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            $query->where('0=1');
            return $dataProvider;
        }
        $query->andFilterWhere(['type'=> $this->type]);
        $query->andFilterWhere(['like', 'key', $this->key])
            ->andFilterWhere(['like', 'value', $this->value])
            ->andFilterWhere(['like', 'created', $this->created])
            ->andFilterWhere(['like', 'updated', $this->updated]);
        return $dataProvider;
    }

    /**
     * @inheritdoc
     */
    public function afterSave( $insert, $changedAttributes )
    {
        parent::afterSave( $insert, $changedAttributes );
        \Yii::$app->{$this->backendCacheComponent}->delete(self::CACHE_NAME);
        \Yii::$app->{$this->frontendCacheComponent}->delete(self::CACHE_NAME);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        parent::afterDelete();
        \Yii::$app->{$this->backendCacheComponent}->delete(self::CACHE_NAME);
        \Yii::$app->{$this->frontendCacheComponent}->delete(self::CACHE_NAME);
    }



    /**
     * Returns default values array
     * @return array
     */
    public static function getDefaultVariables()
    {
        $arr = self::loadDefaultConfigs();
        $types = static::getType();
        foreach($arr as $k=>$v){
            $arr[$k] = [
                'key' => $k,
                'value' => $v,
                'type' => ReflectionHelper::detectVarType($v, $types, static::TYPE_STRING),
            ];
        }
        return $arr;
    }



    /**
     * Validate value by given type
     */
    public function valueValidator()
    {
        if(!empty($this->value) && !$this->hasErrors($this->type) && !empty($this->type) && $type=$this->getType($this->type)){
            $rule = $this->getRules($type);
            if (!empty($rule) && is_array($rule)){
                $validator = Validator::createValidator($rule[0], $this, 'value', array_slice($rule, 1));
                $validator->validateAttribute($this, 'value');
            }
        }
    }

    /**
     * Returns specifically rules by type
     * @param $type
     * @return mixed|null
     */
    public function getRules($type)
    {
        $arr = [
            self::TYPE_BOOLEAN => [ 'boolean', 'strict' => true, 'trueValue' => 'true', 'falseValue' => false ],
            self::TYPE_INTEGER => [ 'integer', 'integerPattern'=>'/\s*\d+\s*/'],
            self::TYPE_NUMBER => [ 'number', 'numberPattern' => '/^\s*[0-9]*\.?[0-9]+\s*$/'],
            self::TYPE_STRING => ['string', 'max' => 255],
            self::TYPE_EMAIL => ['email'],
            self::TYPE_URL => [ 'url'],
            self::TYPE_DATE => [ 'date', 'format' => 'php:Y-m-d'],
            self::TYPE_DATETIME => ['date', 'format' => 'php:Y-m-d H:i:s'],
            self::TYPE_TIME => [ 'date', 'format' => 'php:H:i:s' ],
//            self::TYPE_CURRENCY => ['exist', 'targetAttribute'=>'code', 'targetClass'=>'\common\models\Currency'],
            //self::TYPE_DIRECTORY => [ '\common\validators\DirectoryValidator' ],
            //self::TYPE_FILE => [ '\common\validators\FileValidator' ],
            //self::TYPE_EXCHANGE_RATE_SOURCE => ['in', 'range'=> ['yahoo', 'currencyLayer', 'open'],],
        ];
        return isset($arr[$type])?$arr[$type]:null;
    }

}