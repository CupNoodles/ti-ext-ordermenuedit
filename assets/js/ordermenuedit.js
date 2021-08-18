$(document).ready(function(){
    $('.item-edit-icon').on('click', function(){
        $('#item-edit-'+$(this).data('order-menu-id')).toggle();
    })
    $(document).ajaxSuccess(function(event, request, settings){ 
    if(request.handler == 'onSave'){
        location.reload();
    } });
});