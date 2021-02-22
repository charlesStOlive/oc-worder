<?php namespace Waka\Worder;

use Backend;
use Event;
use Lang;
use System\Classes\PluginBase;
use View;

/**
 * Worder Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = [
        'Waka.Utils',
        'Waka.Informer',
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Worder',
            'description' => 'No description provided yet...',
            'author' => 'Waka',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    public function registerFormWidgets(): array
    {
        return [
            'Waka\Worder\FormWidgets\ShowAttributes' => 'showattributes',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::listen('backend.update.prod', function ($controller) {
            if (get_class($controller) == 'Waka\Worder\Controllers\Documents') {
                return;
            }

            if (in_array('Waka.Worder.Behaviors.WordBehavior', $controller->implement)) {
                $data = [
                    'model' => $modelClass = str_replace('\\', '\\\\', get_class($controller->formGetModel())),
                    'modelId' => $controller->formGetModel()->id,
                ];
                return View::make('waka.worder::publishWord')->withData($data);;
            }
        });
        Event::listen('popup.actions.prod', function ($controller, $model, $id) {
            if (get_class($controller) == 'Waka\Worder\Controllers\Documents') {
                return;
            }

            if (in_array('Waka.Worder.Behaviors.WordBehavior', $controller->implement)) {
                //trace_log("Laligne 1 est ici");
                $data = [
                    'model' => str_replace('\\', '\\\\', $model),
                    'modelId' => $id,
                ];
                return View::make('waka.worder::publishWordContent')->withData($data);;
            }
        });

    }

    public function bootPackages()
    {
        // Get the namespace of the current plugin to use in accessing the Config of the plugin
        $pluginNamespace = str_replace('\\', '.', strtolower(__NAMESPACE__));

        // Instantiate the AliasLoader for any aliases that will be loaded
        $aliasLoader = AliasLoader::getInstance();

        // Get the packages to boot
        $packages = Config::get($pluginNamespace . '::packages');

        // Boot each package
        foreach ($packages as $name => $options) {
            // Setup the configuration for the package, pulling from this plugin's config
            if (!empty($options['config']) && !empty($options['config_namespace'])) {
                Config::set($options['config_namespace'], $options['config']);
            }

            // Register any Service Providers for the package
            if (!empty($options['providers'])) {
                foreach ($options['providers'] as $provider) {
                    App::register($provider);
                }
            }

            // Register any Aliases for the package
            if (!empty($options['aliases'])) {
                foreach ($options['aliases'] as $alias => $path) {
                    $aliasLoader->alias($alias, $path);
                }
            }
        }
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Waka\Worder\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'waka.worder.admin.super' => [
                'tab' => 'Waka - Worder',
                'label' => 'Super Administrateur de Worder',
            ],
            'waka.worder.admin.base' => [
                'tab' => 'Waka - Worder',
                'label' => 'Administrateur de Worder',
            ],
            'waka.worder.user' => [
                'tab' => 'Waka - Worder',
                'label' => 'Utilisateur de Worder',
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [];

    }
    public function registerSettings()
    {
        return [
            'documents' => [
                'label' => Lang::get('waka.worder::lang.menu.documents'),
                'description' => Lang::get('waka.worder::lang.menu.documents_description'),
                'category' => Lang::get('waka.utils::lang.menu.settings_category_model'),
                'icon' => 'icon-file-word-o',
                'url' => Backend::url('waka/worder/documents'),
                'permissions' => ['waka.worder.admin.*'],
                'order' => 10,
            ],
            // 'bloc_types' => [
            //     'label' => Lang::get('waka.worder::lang.menu.bloc_type'),
            //     'description' => Lang::get('waka.worder::lang.menu.bloc_type_description'),
            //     'category' => Lang::get('waka.worder::lang.menu.settings_category'),
            //     'icon' => 'icon-th-large',
            //     'url' => Backend::url('waka/worder/bloctypes'),
            //     'permissions' => ['waka.worder.admin'],
            //     'order' => 1,
            // ],
        ];
    }
}
