<?php

namespace CupNoodles\OrderMenuEdit\Controllers;

use CupNoodles\OrderMenuEdit\Models\Order_Menus;

use Admin\Controllers\Orders as BaseOrders;

use DB;
use Schema;

use Admin\Models\Menus_model;

use Igniter\Flame\Cart\CartItem;

use Admin\Traits\ManagesOrderItems;

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


    public function addMenuItemFromAdmin($order_id, $menu_id, $qty, $order_line_ready){

        $menu = Menus_model::find($menu_id);

        $om_id = Order_Menus::create([
            'order_id' => $order_id,
            'menu_id' => $menu_id,
            'name' => $menu->menu_name,
            'quantity' => $qty,
            'price' => $menu->menu_price,
            'subtotal' => number_format($menu->menu_price * $qty, 4, '.', ''),
            'actual_amt' => $qty,
            'order_line_ready' => $order_line_ready
        ]);


        // optional integration with cupnoodles.pricebyweight. 
        if(Schema::hasTable('units_of_measure') && isset($menu->uom_id) && $menu->uom_id > 0){
            
            DB::table('order_menus as om')
            ->leftJoin('menus as m', 'om.menu_id', '=', 'm.menu_id')
            ->leftJoin('units_of_measure as uom', 'm.uom_id', '=', 'uom.uom_id')
            ->where('order_menu_id', $om_id->order_menu_id)
            ->where('price_by_weight','1')
            ->update(['om.uom_tag' => DB::raw("`".DB::getTablePrefix()."uom`.`short_name`"), 'om.uom_decimals' => DB::raw("`".DB::getTablePrefix()."uom`.`decimal_places`")]);                
    
        }

        // log in order status comments
        $model = $this->formFindModelObject($order_id);
        $model->updateOrderStatus(null, ['comment' => 'Admin added ' .  $qty . ' x ' . $menu->name]);
    }


    // assumes that individual menu lines have already have subtotals calculated correctly
    public function recalcTotalsFromAdmin($order_id){

        $subtotal = Order_Menus::where('order_id', $order_id)->sum('subtotal');

        DB::table('order_totals')
        ->where('order_id', $order_id)
        ->where('code', 'subtotal')
        ->update(['value' => $subtotal]);

        $subtotals = DB::table('order_totals')
        ->select('value')
        ->where('order_id', $order_id)
        ->where('code', '!=', 'subtotal')
        ->where('code', '!=', 'total')
        ->orderBy('priority')
        ->get();

        foreach($subtotals as $sub){
            $subtotal += $sub->value;
        }

        DB::table('order_totals')
        ->where('order_id', $order_id)
        ->where('code', 'total')
        ->update(['value' => $subtotal]);

        $model = $this->formFindModelObject($order_id);
        $model->order_total = $subtotal;
        $model->save();

    }

    /* you cannot edit comments or add menu option in this method! 
    * TODO : replace this method with a CartItem modal that can be added to the admin
    */
    public function edit_onSave($context, $recordId){

        $data = $this->getOrderMenuData();

        foreach($data as $order_menu_id=>$vals){
            if($order_menu_id == 'new'){
                if(isset($vals['menu_id']) && $vals['menu_id'] != 0
                && isset($vals['actual_amt']) && $vals['actual_amt'] != '' && $vals['actual_amt'] > 0){
                    $this->addMenuItemFromAdmin($recordId, $vals['menu_id'], $vals['actual_amt'], isset($vals['order_line_ready']) ? 1 : 0 );
                }
            }
            else{
                $model = Order_Menus::where('order_menu_id', $order_menu_id)->first();

                if(isset($vals['delete']) && $vals['delete'] == 'delete'){

                    $status_model = $this->formFindModelObject($recordId);
                    $status_model->updateOrderStatus(null, ['comment' => 'Admin removed ' .  $model->quantity . ' x ' . $model->name]);

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

                    $vals['subtotal'] = ($vals['actual_amt'] ? $vals['actual_amt'] : $model->quantity ) * $model->price;
                    
                    $update_str = '';
                    foreach($vals as $attr=>$value){
                        if($model->{$attr} != $value){
                            $update_str .= ' ' . $attr . ' : ' . $value; 
                        }
                        $model->{$attr} = $value;
                    }

                    if($update_str != ''){
                        $status_model = $this->formFindModelObject($recordId);
                        $status_model->updateOrderStatus(null, ['comment' => 'Admin updated ' . $model->name . ' ' . $update_str]);
                    }

                    DB::transaction(function () use ($model) {
                            $model->save();
                    });
                }
    
            }
        }

        $this->recalcTotalsFromAdmin($recordId);

        // continue on saving order info
        parent::edit_onSave($context, $recordId);
    }

    protected function getOrderMenuData()
    {
        return post('Order_Menus');
    }

    public function getMenus(){
        $menus = [];
        return Menus_model::isEnabled()->get();
    }

}
