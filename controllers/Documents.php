<?php namespace Waka\Worder\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;

/**
 * Document Back-end Controller
 */
class Documents extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Waka.Utils.Behaviors.BtnsBehavior',
        'Waka.Utils.Behaviors.SideBarUpdate',
        'Waka.Worder.Behaviors.WordBehavior',
        'Backend.Behaviors.ReorderController',
        'Waka.Utils.Behaviors.DuplicateModel',
        'Waka.Informer.Behaviors.PopupInfo',
    ];
    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $btnsConfig = 'config_btns.yaml';
    public $duplicateConfig = 'config_duplicate.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    public $sideBarUpdateConfig = 'config_side_bar_update.yaml';
    //FIN DE LA CONFIG AUTO
    //startKeep/
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Waka.Worder', 'Documents');
    }

    public function update($id)
    {
        $this->bodyClass = 'compact-container';
        return $this->asExtension('FormController')->update($id);
    }


    public function update_onSave($recordId = null)
    {
        $this->asExtension('FormController')->update_onSave($recordId);
        // return [
        //     '#sidebar_attributes' => $this->attributesRender($this->params[0]),
        // ];
        $fieldAttributs = $this->formGetWidget()->renderField('attributs', ['useContainer' => true]);
        $fieldInfos = $this->formGetWidget()->renderField('infos', ['useContainer' => true]);
        //trace_log($fieldInfos);

        return [
            '#Form-field-Document-attributs-group' => $fieldAttributs,
            '#Form-field-Document-infos-group' => $fieldInfos
        ];
    }
        //endKeep/
}

