<?php namespace Waka\Worder\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * ShowAttributes Form Widget
 */

class ShowAttributes extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'waka_worder_show_attributes';

    public $className;
    public $class;
    public $childs;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'className',
            'class',
            'childs',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('showattributes');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
        //
        $this->vars['attributes'] = $this->getAllAttributes($this->className);
    }

    public function getAllAttributes($_model)
    {
        $model = new $_model;
        $model = $model::find(1);
        $columns = $model->getFillable();
        //trace_log($columns);
        // Another option is to get all columns for the table like so:
        // $columns = \Schema::getColumnListing($this->table);
        // but it's safer to just get the fillable fields

        $attributes = $model->getAttributes();
        //trace_log($attributes);

        foreach ($columns as $column) {
            if (!array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }
        //trace_log($attributes);
        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss('css/showattributes.css', 'Waka.Worder');
        $this->addJs('js/showattributes.js', 'Waka.Worder');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return \Backend\Classes\FormField::NO_SAVE_DATA;
    }

    public function onShowAttributes()
    {
        //$this->prepareVars();
        return $this->makePartial('popup');
    }
}
