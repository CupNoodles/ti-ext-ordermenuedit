@php
    $menuItems = $model->getOrderMenus();
    $menuItemsOptions = $model->getOrderMenuOptions();
    $orderTotals = $model->getOrderTotals();
    $menusEnabled =  $this->controller->getMenus();

@endphp
<style>
.item-edit-icon{
    cursor: pointer;
    color: #0000FF;
}
.item-edit{
    display:none;
}
</style>
<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>@lang('cupnoodles.ordermenuedit::default.ordered_amt')</th>
            <th>@lang('cupnoodles.ordermenuedit::default.actual_amt')</th>
            <!-- <th>@lang('cupnoodles.ordermenuedit::default.line_ready')</th> -->
            <th width="65%">@lang('admin::lang.orders.column_name_option')</th>
            <th class="text-left">@lang('admin::lang.orders.column_price')</th>
            <th class="text-right">@lang('admin::lang.orders.column_total')</th>
            <th class="text-right">@lang('cupnoodles.ordermenuedit::default.delete')</th>
        </tr>
        </thead>
        <tbody>
        @foreach($menuItems as $menuItem)
            <tr data-order-menu-edit="order-menu-edit-line" id="order_menu_row_{{$menuItem->order_menu_id}}">
                <td>
                    {{--
                        uom_tag and uom_decimals are introduced in cupnoodles.pricebyweight. While this extension should work independently of that, 
                        it's written to be used together, so ymmv if you're using one and not the other. 
                    --}}
                    @if(isset($menuItem->uom_tag) && $menuItem->uom_tag != '')
                        {{ number_format($menuItem->quantity, $menuItem->uom_decimals) }} 
                        {{ $menuItem->uom_tag }}
                        x
                    @else
                        {{ number_format($menuItem->quantity, 0) }} x
                    @endif
                </td>
                <td>
                    <div class="actual_col">
                        <input type="number" id="order_menu_edit_actual_amt_{{$menuItem->order_menu_id}}" 
                        name="Order_Menus[{{$menuItem->order_menu_id}}][actual_amt]" 
                        @if(isset($menuItem->actual_amt))
                        value="{{ number_format($menuItem->actual_amt, $menuItem->uom_decimals )}}"
                        @endif
                        placeholder="{{ number_format($menuItem->quantity, $menuItem->uom_decimals )}}"
                        pattern="-?\d+(\.\d+)?" 
                        @if(isset($menuItem->uom_decimals) && $menuItem->uom_decimals > 0)
                        step="{{ pow(10, -1 * $menuItem->uom_decimals) }}"
                        @endif
                        />
                    </div>
                </td>
                <!--
                <td>
                    <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" id="order_menu_edit_line_ready_{{$menuItem->order_menu_id}}" class="custom-control-input"  name="Order_Menus[{{$menuItem->order_menu_id}}][order_line_ready]" value="1" {{ $menuItem->order_line_ready ? 'checked' : ''}}/>
                            <label class="custom-control-label" for="order_menu_edit_line_ready_{{$menuItem->order_menu_id}}">
                            </label>
                    </div>
                </td>
                -->
                <td>
                    <i class="fas fa-edit item-edit-icon" data-order-menu-id="{{$menuItem->order_menu_id}}"></i>
                    <b>{{ $menuItem->name }}</b>
                    
                    @if($menuItemOptions = $menuItemsOptions->get($menuItem->order_menu_id))
                        <ul class="list-unstyled ">
                            @foreach($menuItemOptions as $menuItemOption)
                                <li>
                                    {{ $menuItemOption->order_option_name }}&nbsp;
                                    @if($menuItemOption->order_option_price > 0)
                                        ({{ currency_format($menuItemOption->quantity * $menuItemOption->order_option_price) }}
                                        )
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                        <div class="item-edit" id="item-edit-{{$menuItem->order_menu_id}}" data-order-menu-id="{{$menuItem->order_menu_id}}">
                        @php
                            $options = Admin\Models\Menu_item_options_model::where('menu_id', $menuItem->menu_id)->get();
                        @endphp
                            @foreach($options as $menu_option)
                                @php
                                    $checked = false;
                                @endphp
                                <div class="">
                                    {{ $menu_option->option_name }}
                                </div>
                                @foreach($menu_option->option_values as $value)
                                    @php
                                        $checked = false;
                                        if($menuItemOptions = $menuItemsOptions->get($menuItem->order_menu_id)){
                                            foreach($menuItemOptions as $menuItemOption){
                                                if($menu_option->menu_option_id == $menuItemOption->order_menu_option_id){
                                                    if($value->value == $menuItemOption->order_option_name){
                                                        $checked = true;
                                                    }
                                                }
                                            }
                                        }
                                        $menu_option_model = new CupNoodles\OrderMenuEdit\Models\Order_Menu_Options();
                                        $menu_option_model->menu_option_value_id = 0; // only enough we don't have menu_option_value_id here in the admin view, fill it in on backend if it's 0
                                        $menu_option_model->order_option_name = $value->value;
                                        $menu_option_model->order_option_price = $value->price;
                                        $menu_option_model->quantity = $value->quantity;
                                        $menu_option_model->option_value_id = $value->option_value_id;
                                    @endphp
                                    
                                    @if($menu_option['display_type'] == 'radio')
                                        <div class="custom-control custom-radio mb-2">
                                            <input 
                                            type="radio" 
                                            id="order_menu_edit_option_{{$menuItem->order_menu_id}}_{{$menu_option->menu_option_id}}_{{$value->option_value_id}}" 
                                            class="custom-control-input"  name="Order_Menus[{{$menuItem->order_menu_id}}][menu_options][{{$menu_option->menu_option_id}}]" 
                                            value="{{ json_encode([
                                                'menu_option_value_id'=> $menu_option_model->menu_option_value_id,
                                                'order_option_name' => $menu_option_model->order_option_name,
                                                'order_option_price' => $menu_option_model->order_option_price,
                                                'quantity' => $menu_option_model->quantity,
                                                'option_value_id' => $menu_option_model->option_value_id
                                                ]) }}" 
                                            {{ $checked ? 'checked' : ''}} />
                                            <label class="custom-control-label" for="order_menu_edit_option_{{$menuItem->order_menu_id}}_{{$menu_option->menu_option_id}}_{{$value->option_value_id}}">
                                                {{ $value->value }}
                                            </label>
                                        </div>
                                    @elseif($menu_option['display_type'] == 'checkbox')
                                        <div class="custom-control custom-checkbox">
                                            <input 
                                            type="checkbox" 
                                            id="order_menu_edit_option_{{$menuItem->order_menu_id}}_{{$menu_option->menu_option_id}}" 
                                            class="custom-control-input"  
                                            name="Order_Menus[{{$menuItem->order_menu_id}}][menu_options][{{$menu_option->menu_option_id}}]" 
                                            value="{{ json_encode([
                                                'menu_option_value_id'=> $menu_option_model->menu_option_value_id,
                                                'order_option_name' => $menu_option_model->order_option_name,
                                                'order_option_price' => $menu_option_model->order_option_price,
                                                'quantity' => $menu_option_model->quantity,
                                                'option_value_id' => $menu_option_model->option_value_id
                                                ]) }}" 
                                            {{ $checked ? 'checked' : ''}} />
                                            <label class="custom-control-label" for="order_menu_edit_option_{{$menuItem->order_menu_id}}_{{$menu_option->menu_option_id}}">
                                                {{ $value->value }}
                                            </label>
                                        </div>
                                    
                                    @endif
                                
                                @endforeach
                            @endforeach
                        </div>                
                    
                    @if(!empty($menuItem->comment))
                        <p class="font-weight-bold">{{ $menuItem->comment }}</p>
                    @endif
                </td>
                <td class="text-left">{{ currency_format($menuItem->price) }}</td>
                <td class="text-right">{{ currency_format($menuItem->subtotal) }}</td>
                <td>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" id="order_menu_edit_delete_{{$menuItem->order_menu_id}}" class="custom-control-input"  name="Order_Menus[{{$menuItem->order_menu_id}}][delete]" value="delete" />
                        <label class="custom-control-label" for="order_menu_edit_delete_{{$menuItem->order_menu_id}}">
                        </label>
                    </div>
                </td>
            </tr>
        @endforeach
        <tr>
            <td>Add an Item:</td>
            <td><input type="number" id="order_menu_edit_actual_amt_new" 
                        name="Order_Menus[new][actual_amt]" 
                        pattern="-?\d+(\.\d+)?" 
                        /></td>
            <!--
            <td>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" id="order_menu_edit_line_ready_new" class="custom-control-input"  name="Order_Menus[new][order_line_ready]" value="1" />
                    <label class="custom-control-label" for="order_menu_edit_line_ready_new">
                    </label>
                </div>
            </td>
            -->
            <td colspan="4">
                <select id="form-field-coupon-menus" name="Order_Menus[new][menu_id]" data-enable-filtering="" data-enable-case-insensitive-filtering="">
                    <option value="0">
                        --- Select an item to add ---
                    </option>
                    @foreach($menusEnabled as $menu)
                        <option value="{{ $menu['menu_id'] }}">
                            {{ $menu['menu_name']}} 
                        </option>
                    @endforeach
                </select>
            
            </td>
        </tr>
        <tr>
            <td class="border-top p-0" colspan="99999"></td>
        </tr>
        @foreach($orderTotals as $total)
            @continue($model->isCollectionType() AND $total->code == 'delivery')
            @php $thickLine = ($total->code == 'order_total' OR $total->code == 'total') @endphp
            <tr>
                <td
                    class="{{ ($loop->iteration === 1 OR $thickLine) ? 'lead font-weight-bold' : 'text-muted' }}" width="1"
                ></td>
                <td
                    class="{{ ($loop->iteration === 1 OR $thickLine) ? 'lead font-weight-bold' : 'text-muted' }}" width="1"
                ></td>
                <td
                    class="{{ ($loop->iteration === 1 OR $thickLine) ? 'lead font-weight-bold' : 'text-muted' }}" width="1"
                ></td>
                <td
                    class="{{ ($loop->iteration === 1 OR $thickLine) ? 'lead font-weight-bold' : 'text-muted' }}"
                ></td>
                <td
                    class="{{ ($loop->iteration === 1 OR $thickLine) ? 'lead font-weight-bold' : 'text-muted' }} text-left"
                >{{ $total->title }}</td>
                <td
                    class="{{ ($loop->iteration === 1 OR $thickLine) ? 'lead font-weight-bold' : '' }} text-right"
                >{{ currency_format($total->value) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
