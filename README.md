## Order Menu Edit

## Dev Notes

This extension is written specifically for use at osakana.nyc, and therefore is meant to be used in conjunction with the cupnoodles/pricebybweight extentions, and likewise is rather incomplete and shouldn't be considered a general purpose extention. For exmaple, it currently only supports option editing for the types 'checkbox' and 'radio', since those are the only ones used at the main store. The code itself can be used as a proof of concept for others, but it's not recommended to install this on any other stores as-is. 

Order Menu Edit has the following features:

- adds an 'Actual Amount' colunm to order_menus, to log differenced between and ordered amount and a delivered amount
- adds a 'Ready' boolean value to each order menu line
- allows for editing of the 'Actual Amount' and 'Ready' values from within admin
- can add/delete menu lines from admin 
- add/delete/edit actions are logged in the order status history 
- save button is moved to the bottom of the admin order form (this is my personal preference)

# Usage

Installing this plugin should cause the order edit link in the Oders list view to link to cupnoodles/orders/edit as opposed to admin/orders/edit. 
Any existing links to admin/orders/edit will need to be updated by hand, if order editing is required. 