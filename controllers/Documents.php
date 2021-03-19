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
        'waka.Utils.Behaviors.SideBarAttributesBehavior',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ReorderController',
        'Waka.Informer.Behaviors.PopupInfo',
        'Waka.Worder.Behaviors.WordBehavior',
        'Waka.Utils.Behaviors.DuplicateModel',

    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $duplicateConfig = 'config_duplicate.yaml';
    public $sidebarAttributesConfig = 'config_attributes.yaml';

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
}
