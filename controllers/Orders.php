<?php

namespace CupNoodles\OrderMenuEdit\Controllers;

use CupNoodles\OrderMenuEdit\Models\Order_Menus;
use CupNoodles\OrderMenuEdit\Models\Order_Menu_Options;
use Admin\Models\Menu_item_option_values_model;
use Admin\Models\Menu_item_options_model;

use Admin\Controllers\Orders as BaseOrders;

use DB;
use Schema;
use DOMDocument;

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

    public function edit($context, $recordId)
    {
        parent::edit($context, $recordId);
        $this->addJS('extensions/cupnoodles/ordermenuedit/assets/js/ordermenuedit.js', 'cupnoodles-ordermenuedit');
    }

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
        ->select('value', 'code')
        ->where('order_id', $order_id)
        ->where('code', '!=', 'subtotal')
        ->where('code', '!=', 'total')
        ->orderBy('priority')
        ->get();

        foreach($subtotals as $ix=>$sub){

            // this sucks a lot but it's a placeholder until we can re-write ordermenuedit to use Cart object instead of manually sifting through Order object.
            // doesn't matter at all unless you've got TaxClasses extension installed
            
            if($sub->code == 'variableTax'){
                $taxAmount = 0;

                $tax_rates = \CupNoodles\TaxClasses\Models\TaxClasses::all();
                $menus = Order_Menus::where('order_id', $order_id)->with(['order', 'menus'])->get();
                
        
                
        
                foreach($menus as $ix=>$menu){
                    if($menu->menus->tax_class_id && isset($menu->menus->tax_classes->rate)){
                        $taxAmount += $menu->subtotal * ($menu->menus->tax_classes->rate / 100);
                    }                    
                }
                DB::table('order_totals')
                ->where('order_id', $order_id)
                ->where('code', 'variableTax')
                ->update(['value' => $taxAmount]);
            }
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
                $menus_model = Menus_model::where('menu_id', $model->menu_id)->first();
                
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

                    $options = Menu_item_options_model::where('menu_id', $model->menu_id)->get();
                    foreach($options as $menu_option){
                        if(isset($vals['menu_options']) && isset($vals['menu_options'][$menu_option->menu_option_id]) ){ // update or insert
                            $price_diff = 0;
                            foreach($vals['menu_options'] as $order_menu_option_id=>$option_values_str){
                                
                                $option_values = json_decode($option_values_str, false);
                                
                                if(is_object($option_values)){
                                    
                                    if($option_values->menu_option_value_id == 0 ){
                                        
                                        $option_value_model  = Menu_item_option_values_model::where([
                                            ['menu_option_id', '=', $order_menu_option_id],
                                            ['option_value_id', '=', $option_values->option_value_id]
                                        ])->first();
                                        $option_values->menu_option_value_id = $option_value_model->menu_option_value_id;
                                    }
                                    // build options from [post data]
                                    $option_data = 
                                    ['order_id' => $recordId,
                                    'menu_id' => $model->menu_id,
                                    'menu_option_value_id' => $option_values->menu_option_value_id,
                                    'order_option_name' => $option_values->order_option_name,
                                    'order_option_price' => $option_values->order_option_price
                                    ];
                                    if(isset($vals['actual_amt']) && $vals['actual_amt'] != '') {
                                        $option_data['quantity'] = $vals['actual_amt'];
                                        $price_qty = $vals['actual_amt'];
                                    }
                                    else{
                                        $price_qty = $model->quantity;
                                    }
                                    Order_Menu_Options::updateOrCreate(['order_menu_id' => $order_menu_id,'order_menu_option_id' => $order_menu_option_id], $option_data);
                                    if($option_values->order_option_price != 0){
                                        $price_diff += $option_values->order_option_price;
                                    }
                                }

                            }
                        }
                        else{ // delete this line (checkbox unchecked)
                            $order_menu_options = Order_Menu_Options::where(['order_menu_id' => $order_menu_id,'order_menu_option_id' => $menu_option->menu_option_id]);
                            $order_menu_options->delete();
                        }
                    }
                    foreach($vals as $attr=>$value){
                        if($attr != 'menu_options'){
                            if($model->{$attr} != $value){
                                $update_str .= ' ' . $attr . ' : ' . $value; 
                            }
                            $model->{$attr} = $value;
                        }
                    }
                    $model->price = $menus_model->menu_price + $price_diff;
                    $model->subtotal = $model->price * $price_qty;


                    if($update_str != ''){
                        $status_model = $this->formFindModelObject($recordId);
                        $status_model->updateOrderStatus(null, ['comment' => 'Updated ' . $model->name . ' ' . $update_str]);
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

    // so this i pretty janky but I prefer the save buttons on the bottom of the form. 
    public function renderForm($options = []){
        $r =  parent::renderForm($options); 
        $dom = new DOMDocument();
        $r = str_replace(' & ', ' &amp; ', $r);
        
        $dom->loadHTML($r);

        $divs = $dom->getElementsByTagName('div')->item(0);
        $divs->parentNode->removeChild($divs);
        $dom->appendChild($divs);
        return $dom->saveHTML();
        
    }

    protected function getOrderMenuData()
    {
        return post('Order_Menus');
    }

    public function getMenus(){
        $menus = [];
        return Menus_model::isEnabled()->get();
    }

    public function orderMenuOptionsQuery()
    {
        return DB::table('order_menu_options');
    }
}
