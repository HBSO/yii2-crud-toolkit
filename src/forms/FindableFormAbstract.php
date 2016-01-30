<?php

namespace voskobovich\crud\forms;

use voskobovich\base\traits\ModelTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveRecord;


/**
 * Class FindableFormAbstract
 * @package voskobovich\crud\forms
 *
 * @property ActiveRecord $source
 */
abstract class FindableFormAbstract extends Model
{
    use ModelTrait;

    /**
     * Editable model class name
     * @var string
     */
    public static $sourceClass;

    /**
     * Default attribute for print error
     * @var string
     */
    public $sourceDefaultAttribute;

    /**
     * Default scenario for editable model
     * @var string
     */
    public $sourceScenario = ActiveRecord::SCENARIO_DEFAULT;

    /**
     * @var ActiveRecord
     */
    protected $_source;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!static::$sourceClass) {
            throw new InvalidConfigException('Property "sourceClass" can not be empty.');
        }

        if (!is_subclass_of(static::$sourceClass, ActiveRecord::className())) {
            throw new InvalidConfigException('Property "sourceClass" must be implemented ' . ActiveRecord::className());
        }

        if (!$this->sourceDefaultAttribute) {
            throw new InvalidConfigException('Property "sourceDefaultAttribute" can not be empty.');
        }

        $this->_source = new static::$sourceClass();

        parent::init();
    }

    /**
     * @param $id
     * @return null|ActiveRecord
     */
    public static function findOne($id)
    {
        /** @var ActiveRecord $source */
        $source = static::$sourceClass;
        $source = $source::findOne($id);

        if ($source == null) {
            return null;
        }

        $model = new static();
        $model->setSource($source);

        return $model;
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not saved due to validation error.', __METHOD__);
            return false;
        }

        $this->_source->load($this->getAttributes(), '');

        if (!$this->_source->save()) {
            $this->populateErrors($this->_source, $this->sourceDefaultAttribute);
            return false;
        }

        return true;
    }

    /**
     * @param ActiveRecord $model
     * @param string $defaultAttribute
     * @param array $attributesMap
     */
    public function populateErrors(ActiveRecord $model, $defaultAttribute, $attributesMap = [])
    {
        /** @var Model $this */
        $errors = $model->getErrors();

        foreach ($errors as $attribute => $messages) {
            $attribute = isset($attributesMap[$attribute])
                ? $attributesMap[$attribute]
                : $attribute;
            if (false === $this->hasProperty($attribute)) {
                if (!method_exists($this, 'hasAttribute')) {
                    $attribute = $defaultAttribute;
                } elseif (false === $this->hasAttribute($attribute)) {
                    $attribute = $defaultAttribute;
                }
            }
            foreach ($messages as $mes) {
                $this->addError($attribute, $mes);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->_source->getPrimaryKey();
    }

    /**
     * @param ActiveRecord $value
     */
    public function setSource(ActiveRecord $value)
    {
        $this->_source = $value;
        $this->_source->scenario = $this->sourceScenario;
        $this->load($this->_source->getAttributes(), '');
    }

    /**
     * @return ActiveRecord
     */
    public function getSource()
    {
        return $this->_source;
    }
}