
$( ".search" ).keyup(function() {
        var id = $( this ).data("id");
        var val = $( this ).val();
        
        if (val.length > 2) {
            val = encodeURIComponent(val);

            request = $.ajax({
                url: "ajax/search.php",
                data: "id=" + id + "&val=" + val, 
                type: "post",
                success: function(html) { 
                    $(" #searchResults ").show();
                    $(" #searchResults ").html(html);
                }
            });        
        
        
        
        }
        
        
        /*

     * 
         */
});    



