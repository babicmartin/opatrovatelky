
$( ".updateSelect" ).change(function() {
    var id = $( this ).data("id");
    var table = $( this ).data("table");
    var val = $( this ).val();
    var column = $( this ).data("column");
    
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


$( ".updateSelectReload" ).change(function() {
    var id = $( this ).data("id");
    var table = $( this ).data("table");
    var val = $( this ).val();
    var column = $( this ).data("column");
    
    
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

