<?php

namespace CupNoodles\OrderMenuEdit\Models;

use Model;

class Order_Menus extends Model{

    protected $table = 'order_menus';

    protected $primaryKey = 'order_menu_id';

    public $relation = [
        'belongsTo' => [
            'order' => ['Admin\Models\Orders_model'],
        ],
    ];

}
