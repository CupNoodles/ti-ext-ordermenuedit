<?php 

namespace CupNoodles\OrderMenuEdit;


use System\Classes\BaseExtension;


// Admin-UI
use Event;
use Admin\Models\Menus_model;
use Admin\Widgets\Form;
use Admin\Widgets\Toolbar;
use Admin\Models\Orders_model;

use Admin\Classes\AdminController;
use Admin\Controllers\Orders;

use Igniter\Cart\Classes\CartManager;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Butcher Extension Information File
 */
class Extension extends BaseExtension
{
    /**
     * Returns information about this extension.
     *
     * @return array
     */
    public function extensionMeta()
    {
        return [
            'name'        => 'OrderMenuEdit',
            'author'      => 'CupNoodles',
            'description' => 'Edit Order Menu Items in admin panel.',
            'icon'        => 'fa-edit',
            'version'     => '1.0.0'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {

        // Push extension views into $partialPath since this isn't a formwidget
        AdminController::extend(function ($controller) {
            if( in_array('~/app/admin/views/orders', $controller->partialPath)){
                array_unshift($controller->partialPath, '~/extensions/cupnoodles/ordermenuedit/views');
            }
        });

        // Enable save buttons on Order View
        Event::listen('admin.form.extendFieldsBefore', function (Form $form) {
            if ($form->model instanceof Orders_model) {
                Event::listen('admin.toolbar.extendButtons', function (Toolbar $toolbar) {
                    $toolbar->buttons['save']['context'][] = 'edit';
                    $toolbar->buttons['saveClose']['context'][] = 'edit';
                });						
            }
        });


        // Change the edit link on Orders List View to cupnoodles/ordermenuedit/edit{id} so that the form eventually submits to the extended controller
        Orders::extend(function ($controller){
            if($controller->listConfig['list']['model'] == 'Admin\Models\Orders_model'){
                $controller->listConfig['list']['configFile'] = 'extensions/cupnoodles/ordermenuedit/models/config/orders_model';
            }
        });

    }


    public function registerFormWidgets()
    {
        return ['CupNoodles\PriceByWeight\FormWidgets\OrderMenuEdit' => [
            'label' => 'Order Menu Edit',
            'code' => 'cupnoodles\ordermenuedit',
        ]];
    }

    /**
     * Registers any front-end components implemented in this extension.
     *
     * @return array
     */
    public function registerComponents()
    {

    }

    /**
     * Registers any admin permissions used by this extension.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'Admin.UnitsOfMeasure' => [
                'label' => 'cupnoodles.ordermenuedit::default.permissions',
                'group' => 'admin::lang.permissions.name',
            ],
        ];
    }

    


    public function registerNavigation()
    {

    }
}
