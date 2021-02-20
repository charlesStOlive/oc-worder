<?php

namespace Waka\Worder\Behaviors;

use Backend\Classes\ControllerBehavior;
use Redirect;
use Waka\Utils\Classes\DataSource;
use Waka\Worder\Classes\WordCreator2;
use Waka\Worder\Classes\WordProcessor2;
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
        $model = post('model');
        $modelId = post('modelId');

        $ds = new DataSource($model, 'class');
        $options = $ds->getPartialOptions($modelId, 'Waka\Worder\Models\Document');

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;

        return $this->makePartial('$/waka/worder/behaviors/wordbehavior/_popup.htm');
    }
    public function onLoadWordBehaviorContentForm()
    {
        $model = post('model');
        $modelId = post('modelId');

        $ds = new DataSource($model, 'class');
        $options = $ds->getPartialOptions($modelId, 'Waka\Worder\Models\Document');

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;
        //$this->vars['modelClassName'] = $model;

        return [
            '#popupActionContent' => $this->makePartial('$/waka/worder/behaviors/wordbehavior/_content.htm'),
        ];
    }

    public function onWordBehaviorPopupValidation()
    {
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $docId = post('documentId');
        $modelId = post('modelId');

        return Redirect::to('/backend/waka/worder/documents/makeword/?docId=' . $docId . '&modelId=' . $modelId);
    }

    public function onCloudWordValidation()
    {
        $errors = $this->CheckValidation(\Input::all());
        if ($errors) {
            throw new \ValidationException(['error' => $errors]);
        }
        $docId = post('documentId');
        $modelId = post('modelId');
        //$modelClassName = post('modelClassName');

        $wc = new WordCreator2($docId);
        return $wc->renderCloud($modelId);
    }
    /**
     * Validations
     */
    public function CheckValidation($inputs)
    {
        $rules = [
            'modelId' => 'required',
            'documentId' => 'required',
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
        $id = post('id');
        $wp = new WordProcessor2($id);
        $tags = $wp->checkTags();
        $documentTestId = Document::find($id)->test_id;
        if ($documentTestId) {
            $modelId = $documentTestId;
            return Redirect::to('/backend/waka/worder/documents/makeword/?docId=' . $id . '&modelId=' . $modelId);
        } else {
            return Redirect::to('/backend/waka/worder/documents/makeword/?docId=' . $id);
        }

    }
    public function makeword()
    {
        $docId = post('docId');
        $modelId = post('modelId');
        $wc = new WordCreator2($docId);
        return $wc->renderWord($modelId);
    }

    public function onLoadWordCheck()
    {
        $id = post('id');
        $wp = new WordProcessor2($id);
        return $wp->checkDocument();
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
