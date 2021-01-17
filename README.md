## Order Menu Edit

Order Menu Edit has the following features:

- adds an 'Actual Amount' colunm to order_menus, to log differenced between and ordered amount and a delivered amount
- adds a 'Ready' boolean value to each order menu line
- allows for editing of the 'Actual Amount' and 'Ready' values from within admin
- can add/delete menu lines from admin 
- add/delete/edit actions are logged in the order status history 
- save button is moved to the bottom of the admin order form (this is my personal preference)

What needs to be added still: 

- a modal formwidget to edit menu lines would be much cleaner, and allow for editing of order line comments as well as option values. Until that's in place, menu options cannot be edited through the admin. 
- add/delete should be handled by modal UI to clean up the admin page

# Usage

Installing this plugin should cause the order edit link in the Oders list view to link to cupnoodles/orders/edit as opposed to admin/orders/edit. 
Any existing links to admin/orders/edit will need to be updated by hand, if order editing is required. 