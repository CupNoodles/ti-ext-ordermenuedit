@php
    $menuItems = $model->getOrderMenus();
    $menuItemsOptions = $model->getOrderMenuOptions();
    $orderTotals = $model->getOrderTotals();
@endphp
<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>@lang('cupnoodles.pricebyweight::default.ordered_amt')</th>
            <th>@lang('cupnoodles.pricebyweight::default.real_weight')</th>
            <th width="65%">@lang('admin::lang.orders.column_name_option')</th>
            <th class="text-left">@lang('admin::lang.orders.column_price')</th>
            <th class="text-right">@lang('admin::lang.orders.column_total')</th>
        </tr>
        </thead>
        <tbody>
        @foreach($menuItems as $menuItem)
            <tr data-order-menu-edit="order-menu-edit-line" id="order_menu_row_{{$menuItem->order_menu_id}}">
                <td>
                    @if($menuItem->uom_tag != '')
                        {{ number_format($menuItem->quantity, $menuItem->uom_decimals) }} 
                        {{ $menuItem->uom_tag }}
                        x
                    @else
                        {{ number_format($menuItem->quantity, 0) }} x
                    @endif
                </td>
                <td>
                    <div class="actual_col">
                        <input type="number" id="order_menu_edit_actual_amt" name="Order_Menus[{{$menuItem->order_menu_id}}][actual_amt]" value="{{$menuItem->actual_amt}}"/>
                        <input type="checkbox" />
                    </div>
                </td>
                <td><b>{{ $menuItem->name }}</b>
                    @if($menuItemOptions = $menuItemsOptions->get($menuItem->order_menu_id))
                        <ul class="list-unstyled">
                            @foreach($menuItemOptions as $menuItemOption)
                                <li>
                                    {{ $menuItemOption->quantity }}x
                                    {{ $menuItemOption->order_option_name }}&nbsp;
                                    @if($menuItemOption->order_option_price > 0)
                                        ({{ currency_format($menuItemOption->quantity * $menuItemOption->order_option_price) }}
                                        )
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if(!empty($menuItem->comment))
                        <p class="font-weight-bold">{{ $menuItem->comment }}</p>
                    @endif
                </td>
                <td class="text-left">{{ currency_format($menuItem->price) }}</td>
                <td class="text-right">{{ currency_format($menuItem->subtotal) }}</td>
            </tr>
        @endforeach
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
