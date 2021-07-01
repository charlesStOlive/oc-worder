<?php namespace Waka\Worder\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use Waka\Worder\Models\Document;

/**
 * Document Back-end Controller
 */
class Documents extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Waka.Utils.Behaviors.BtnsBehavior',
        'waka.Utils.Behaviors.SideBarAttributesBehavior',
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
    public $sidebarAttributesConfig = 'config_attributes.yaml';    
    //FIN DE LA CONFIG AUTO

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Waka.Worder', 'Documents');
    }

    //startKeep/

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

    public function formExtendFieldsBefore($form) {
        if(!$this->user->hasAccess(['waka.worder.admin.super'])) {
            //Le blocage du champs code de ask est fait dans le model wakaMail
            $model =  Document::find($this->params[0]);
            $countAsks = 0;
            if($model->asks) {
                $countAsks = count($model->asks);
                $form->tabs['fields']['asks']['maxItems'] = $countAsks;
                $form->tabs['fields']['asks']['minItems'] = $countAsks;
            }
        }
    }
        //endKeep/
}
