
$( ".updateInput" ).change(function() {
    $( ".updateInput" ).focusout(function() {
        var id = $( this ).data("id");
        var table = $( this ).data("table");
        var val = $( this ).val();
        var column = $( this ).data("column");
        
        val = encodeURIComponent(val);

        //pass this to ajax result
        var elem = $(this);
        
        request = $.ajax({
            url: "ajax/updateCrud.php",
            data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val, 
            type: "post",
            success: function(html) {     
                elem.css('border', '2px solid #8A2062');
                setTimeout(function(){           
                    elem.css('border', '1px solid #CED4DA');
                 }, 2000);


                //window.location.reload();
                //$(" #help ").html(html);
                //alert(html);
            }
        });
    });
});    


$( ".updateInputReload" ).change(function() {
    $( ".updateInputReload" ).focusout(function() {
        var id = $( this ).data("id");
        var table = $( this ).data("table");
        var val = $( this ).val();
        var column = $( this ).data("column");
        
        val = encodeURIComponent(val);
        
        
        
        request = $.ajax({
            url: "ajax/updateCrud.php",
            data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val, 
            type: "post",
            success: function(html) {     

                window.location.reload();
                //$(" #help ").html(html);
                //alert(html);
            }
        });
    });
});    

$( ".updateInputSpecial" ).change(function() {
    $( ".updateInputSpecial" ).focusout(function() {
        var id = $( this ).data("id");
        var table = $( this ).data("table");
        var val = $( this ).val();
        var column = $( this ).data("column");
        var type = $( this ).data("type");
        
        val = val.replace("+", "%2B");
        val = val.replace("&", "%26");


        request = $.ajax({
            url: "ajax/updateCrudSpecial.php",
            data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val + "&type=" + type, 
            type: "post",
            success: function(html) {     

                window.location.reload();
                //$(" #help ").html(html);
                //alert(html);
            }
        });
    });
}); 


$( ".updateInputSpecialNotReload" ).change(function() {
    $( ".updateInputSpecialNotReload" ).focusout(function() {
        var id = $( this ).data("id");
        var table = $( this ).data("table");
        var val = $( this ).val();
        var column = $( this ).data("column");
        var type = $( this ).data("type");
        
        val = val.replace("+", "%2B");
        val = val.replace("&", "%26");

        //alert(val);

        request = $.ajax({
            url: "ajax/updateCrudSpecial.php",
            data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val + "&type=" + type, 
            type: "post",
            success: function(html) {     

                //window.location.reload();
                //$(" #help ").html(html);
                //alert(html);
            }
        });
    });
}); 


