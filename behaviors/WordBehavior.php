<?php namespace Waka\Worder\Behaviors;

use Backend\Classes\ControllerBehavior;
use Redirect;
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

    public function getDataSourceFromModel(String $model)
    {
        $modelClassDecouped = explode('\\', $model);
        $modelClassName = array_pop($modelClassDecouped);
        return \Waka\Utils\Models\DataSource::where('model', '=', $modelClassName)->first();
    }

    public function getModel($model, $modelId)
    {
        $myModel = $model::find($modelId);
        return $myModel;
    }

    /**
     * LOAD DES POPUPS
     */
    public function onLoadWordBehaviorPopupForm()
    {
        $model = post('model');
        $modelId = post('modelId');

        $dataSource = $this->getDataSourceFromModel($model);
        $options = $dataSource->getPartialOptions($modelId, 'Waka\Worder\Models\Document');

        $this->vars['options'] = $options;
        $this->vars['modelId'] = $modelId;
        //$this->vars['modelClassName'] = $model;

        // $this->vars['dataSrcId'] = $dataSource->id;

        return $this->makePartial('$/waka/worder/behaviors/wordbehavior/_popup.htm');
    }
    public function onLoadWordBehaviorContentForm()
    {
        $model = post('model');
        $modelId = post('modelId');

        $dataSource = $this->getDataSourceFromModel($model);
        $options = $dataSource->getPartialOptions($modelId, 'Waka\Worder\Models\Document');

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
        return Redirect::to('/backend/waka/worder/documents/makeword/?docId=' . $id);
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
