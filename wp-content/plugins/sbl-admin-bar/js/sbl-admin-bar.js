// sbl admin bar
jQuery(document).ready(function($) {

    var keys = {};

    var abVars = JSON.parse(abVarsJson);
    
    var adminBarFlag = abVars.flag;
    
    function doAdminAjax(theFlag) {
                
        $.ajax({
            type: "post",url: abVars.url,
            data: { 
                action: 'sblAdminBar', _ajax_nonce: abVars.nonce, 
                value: theFlag,
            },
            success: function(response){

                if (response !== 'success') {
                    alert(response);				  
                }
            }
        });    
        
    }
    
    $(document).keydown(function (e) {
        
        keys[e.which] = true;
        
        if (keys[16] && keys[17] && keys[65]) {

            e.preventDefault();
            
            if (adminBarFlag == 1) {
                
                adminBarFlag = 0;
                
                doAdminAjax(adminBarFlag);
                
                $( '#admin_bar_msg' ).text('Admin Bar Off');
            
                $( '#admin_bar_msg' ).show(function(){
                    $(this).fadeOut(3000);
                });
                
            } else {
                
                adminBarFlag = 1;
                
                doAdminAjax(adminBarFlag);
                
                $( '#admin_bar_msg' ).text('Admin Bar On');
            
                $( '#admin_bar_msg' ).show(function(){
                    $(this).fadeOut(3000);
                });
                
            }

        }
    });
    
    $(document).keyup(function (e) {
        
        keys[e.which] = false;
        
    });

});
