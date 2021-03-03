<?php

namespace CupNoodles\OrderMenuEdit\Models;

use Model;

class Order_Menus extends Model{

    protected $table = 'order_menus';

    protected $primaryKey = 'order_menu_id';

    protected $fillable = [
        'order_id',
        'menu_id',
        'name',
        'quantity',
        'price',
        'subtotal',
        'actual_amt',
        'order_line_ready'
    ];
    public $relation = [
        'belongsTo' => [
            'order' => ['Admin\Models\Orders_model'],
        ],
    ];

}
