<?php

namespace CupNoodles\OrderMenuEdit\Models;

use Model;

class Order_Menu_Options extends Model{

    protected $table = 'order_menu_options';

    protected $primaryKey = 'order_option_id';

    protected $fillable = [
        'order_id',
        'menu_id',
        'order_option_name',
        'order_option_price',  
        'order_menu_id', 
        'order_menu_option_id',
        'menu_option_value_id',
        'quantity' 
    ];
    public $relation = [
        'belongsTo' => [
            'order' => ['CupNoodles\OrderMenuEdit\Models\Order_Menus'],
        ],
    ];

}
