
$( ".updatePassword" ).change(function() {
    $( ".updatePassword" ).focusout(function() {
        var id = $( this ).data("id");
        var table = $( this ).data("table");
        var val = $( this ).val();
        var column = $( this ).data("column");
        
        if (confirm("Naozaj chcete nastaviť nové heslo?")) {
            request = $.ajax({
                url: "ajax/updatePassword.php",
                data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val, 
                type: "post",
                success: function(html) {     

                    //window.location.reload();
                    //$(" #help ").html(html);
                   //alert(html);
                   alert("Vaše heslo bolo zmenené");
                }
            });
        }
    });
});    



