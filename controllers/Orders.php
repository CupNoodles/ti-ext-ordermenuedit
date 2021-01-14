<?php

namespace CupNoodles\OrderMenuEdit\Controllers;

use CupNoodles\OrderMenuEdit\Models\Order_Menus;

use Admin\Controllers\Orders as BaseOrders;

use DB;

class Orders extends BaseOrders{

    public $formConfig = [
        'name' => 'lang:admin::lang.orders.text_form_name',
        'model' => 'Admin\Models\Orders_model',
        'request' => 'Admin\Requests\Order',
        'edit' => [
            'title' => 'lang:admin::lang.form.edit_title',
            'redirect' => 'orders/edit/{order_id}',
            'redirectClose' => 'orders',
        ],
        'preview' => [
            'title' => 'lang:admin::lang.form.preview_title',
            'redirect' => 'orders',
        ],
        'delete' => [
            'redirect' => 'orders',
        ],
        'configFile' => 'orders_model',
    ];


    public function edit_onSave($context, $recordId){

        $data = $this->getOrderMenuData();

        foreach($data as $order_menu_id=>$vals){
            if($order_menu_id == 'new'){

            }
            else{
                $model = Order_Menus::where('order_menu_id', $order_menu_id)->first();

                if(isset($vals['delete']) && $vals['delete'] == 'delete'){
                    $model->delete();
                }
                else{
                    // order_line_ready is a checkbox, fill it in as false if we don't get a value in post data. 
                    if(!isset($vals['order_line_ready'])){
                        $vals['order_line_ready'] = 0;
                    }
                    if(isset($vals['actual_amt']) && $vals['actual_amt'] == ''){
                        $vals['actual_amt'] = null;
                    }
                    foreach($vals as $attr=>$value){
                        $model->{$attr} = $value;
                    }
        
                    DB::transaction(function () use ($model) {
                            $model->save();
                    });
                }
    
            }
        }

        // continue on saving order info
        parent::edit_onSave($context, $recordId);
    }

    protected function getOrderMenuData()
    {
        return post('Order_Menus');
    }

}
