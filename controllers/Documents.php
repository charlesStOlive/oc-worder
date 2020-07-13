<?php namespace Waka\Worder\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;

/**
 * Documents Back-end Controller
 */
class Documents extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ReorderController',
        'Waka.Informer.Behaviors.PopupInfo',
        'Waka.Worder.Behaviors.WordBehavior',
        'Waka.Utils.Behaviors.DuplicateModel',
        'waka.Utils.Behaviors.SideBarAttributesBehavior',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $duplicateConfig = 'config_duplicate.yaml';
    public $sidebarInfoConfig = '$/waka/crsm/config/config_documents_attributes.yaml';

    public $reorderConfig = 'config_reorder.yaml';
    public $contextContent;

    public $sidebarAttributes;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Waka.Worder', 'documents');

    }

    public function update($id)
    {
        $this->bodyClass = 'compact-container';
        return $this->asExtension('FormController')->update($id);
    }

    public function update_onSave($recordId = null)
    {
        $this->asExtension('FormController')->update_onSave($recordId);
        return [
            '#sidebar_attributes' => $this->attributesRender($this->params[0]),
        ];
    }

    // public function onCreateItem()
    // {
    //     $bloc = $this->getBlocModel();

    //     $data = post($bloc->bloc_type->code . 'Form');
    //     $sk = post('_session_key');
    //     $bloc->delete_informs();

    //     $model = new \Waka\Worder\Models\Content;
    //     $model->fill($data);
    //     $model->save();

    //     $bloc->contents()->add($model, $sk);

    //     return $this->refreshOrderItemList($sk);
    // }

    // public function onUpdateContent()
    // {
    //     $bloc = $this->getBlocModel();

    //     $recordId = post('record_id');
    //     $data = post($bloc->bloc_type->code . 'Form');
    //     $sk = post('_session_key');

    //     $model = \Waka\Worder\Models\Content::find($recordId);
    //     $model->fill($data);
    //     $model->save();

    //     return $this->refreshOrderItemList($sk);
    // }

    // public function onDeleteItem()
    // {
    //     $recordId = post('record_id');
    //     $sk = post('_session_key');

    //     $model = \Waka\Worder\Models\Content::find($recordId);

    //     $bloc = $this->getBlocModel();
    //     $bloc->contents()->remove($model, $sk);

    //     return $this->refreshOrderItemList($sk);
    // }

    // protected function refreshOrderItemList($sk)
    // {
    //     $bloc = $this->getBlocModel();
    //     $contents = $bloc->contents()->withDeferred($sk)->get();

    //     $this->vars['contents'] = $contents;
    //     $this->vars['bloc_type'] = $bloc->bloc_type;
    //     return [
    //         '#contentList' => $this->makePartial('content_list'),
    //     ];
    // }

    // public function getBlocModel()
    // {
    //     $manageId = post('manage_id');

    //     $bloc = $manageId
    //     ? \Waka\Worder\Models\Bloc::find($manageId)
    //     : new \Waka\Worder\Models\Bloc;

    //     return $bloc;
    // }
    // public function relationExtendManageWidget($widget, $field, $model)
    // {
    //     $widget->bindEvent('form.extendFields', function () use ($widget) {

    //         if (!$widget->model->bloc_type) {
    //             return;
    //         }

    //         $options = [];

    //         $yaml = Yaml::parse($widget->model->bloc_type->config);

    //         $modelOptions = $yaml['model']['options'] ?? false;
    //         if ($modelOptions) {
    //             foreach ($modelOptions as $key => $opt) {
    //                 $options[$key] = $opt;
    //             }
    //         }

    //         $fields = $yaml['fields'];
    //         foreach ($fields as $field) {
    //             if ($field['options'] ?? false) {
    //                 foreach ($field['options'] as $key => $opt) {
    //                     $options[$key] = $opt;
    //                 }

    //             }
    //         }
    //         if (count($options) > 0 ?? false) {
    //             $fieldtoAdd = [
    //                 'bloc_form' => [
    //                     'tab' => 'content',
    //                     'type' => 'nestedform',
    //                     'usePanelStyles' => false,
    //                     'form' => [
    //                         'fields' => $options,
    //                     ],
    //                 ],
    //             ];
    //             $widget->addTabFields($fieldtoAdd);
    //         }

    //     });
    // }

}
