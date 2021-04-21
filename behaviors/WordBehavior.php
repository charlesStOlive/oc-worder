<?php

namespace Waka\Worder\Behaviors;

use Backend\Classes\ControllerBehavior;
use Redirect;
use Waka\Utils\Classes\DataSource;
use Waka\Worder\Classes\WordCreator;
use Waka\Worder\Models\Document;

class WordBehavior extends ControllerBehavior
{
    use \Waka\Utils\Classes\Traits\StringRelation;

    protected $wordBehaviorWidget;

    public function __construct($controller)
    {
        parent::__construct($controller);
        $this->wordBehaviorWidget = $this->createWordBehaviorWidget();
    }

    /**
     * METHODES
     */

    /**
     * LOAD DES POPUPS
     */
    public function onLoadWordBehaviorPopupForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        if($options) {
            return $this->makePartial('$/waka/worder/behaviors/wordbehavior/_popup.htm');
        } else {
            return $this->makePartial('$/waka/utils/views/_popup_no_model.htm');
        }

        
    }
    public function onLoadWordBehaviorContentForm()
    {
        $modelClass = post('modelClass');
        $modelId = post('modelId');

        $ds = new DataSource($modelClass, 'class');
        $options = $ds->getProductorOptions('Waka\Worder\Models\Document', $modelId);

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;
        //$this->vars['modelClassName'] = $model;

        if($options) {
            return ['#popupActionContent' => $this->makePartial('$/waka/worder/behaviors/wordbehavior/_content.htm')];
        } else {
            return ['#popupActionContent' => $this->makePartial('$/waka/utils/views/_content_no_model.htm')];
        }

        
    }

    public function onWordBehaviorPopupValidation()
    {
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $productorId = post('productorId');
        $modelId = post('modelId');

        return Redirect::to('/backend/waka/worder/documents/makeword/?productorId=' . $productorId . '&modelId=' . $modelId);
    }

    /**
     * Validations
     */
    public function CheckValidation($inputs)
    {
        $rules = [
            'modelId' => 'required',
            'productorId' => 'required',
        ];

        $validator = \Validator::make($inputs, $rules);

        if ($validator->fails()) {
            return $validator->messages()->first();
        } else {
            return false;
        }
    }
    /**
     * Cette fonction est utilisé lors du test depuis le controller document.
     */
    public function onLoadWordBehaviorForm()
    {
        $productorId = post('productorId');
        $documentTestId = Document::find($productorId)->test_id;
        if ($documentTestId) {
            $modelId = $documentTestId;
            //trace_log($modelId);
            return Redirect::to('/backend/waka/worder/documents/makeword/?productorId=' . $productorId . '&modelId=' . $modelId);
        } else {
            throw new \ValidationException(['error' => "Choisissez un modèle de test"]);
        }
    }
    public function makeword()
    {
        $productorId = post('productorId');
        $modelId = post('modelId');
        //trace_log($modelId);
        
        return WordCreator::find($productorId)->setModelId($modelId)->renderWord();
    }

    public function onLoadWordCheck()
    {
        $productorId = post('productorId');
        $productor = WordCreator::find($productorId);
        $modelTest = $productor->getProductor()->test_id;
        if(!$modelTest) {
            throw new \ValidationException(['test_id' => "Le modèle de test n'est pas renseigné ou n'existe plus"]);
        }
        return $productor->setModelId($modelTest)->checkDocument();
    }

    public function createWordBehaviorWidget()
    {
        $config = $this->makeConfig('$/waka/worder/models/document/fields_for_test.yaml');
        $config->alias = 'wordBehaviorformWidget';
        $config->arrayName = 'wordBehavior_array';
        $config->model = new Document();
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();
        return $widget;
    }
}
