
$( ".deactivateRow" ).click(function(e) {
    e.preventDefault();
    var table = $( this ).data("table");
    var id = $( this ).data("id");
    
    if (confirm('Naozaj chceš odstrániť záznam?')) {
        //pridam cisty riadok do tabulky
        $.ajax({
            url: "ajax/hideRow.php",
            data: "table=" + table + "&id=" + id, 
            type: "post",
            success: function(html) {
                
                window.location.reload();
                  
            }
        });  
    }
});

$( ".addRow" ).click(function() {
    var table = $( this ).data("table");
    
    if (confirm("Naozaj chceš pridať nový záznam?")) {
        //pridam cisty riadok do tabulky
        $.ajax({
            url: "ajax/addRow.php",
            data: "table=" + table, 
            type: "post",
            success: function(html) {
                //alert(html);
                window.location.reload();
                  
            }
        });  
    }

});

$( ".addRowBabySitter" ).click(function() {
    var type = $( this ).data("type");

    if (confirm("Naozaj chceš pridať nový záznam?")) {
        //pridam cisty riadok do tabulky
        $.ajax({
            url: "ajax/addRowBabysitter.php",
            data: "type=" + type,
            type: "post",
            success: function(html) {
                //alert(html);
                window.location.reload();
            }
        });
    }
});

$( ".addRowFamily" ).click(function() {
    var type = $( this ).data("type");

    if (confirm("Naozaj chceš pridať nový záznam?")) {
        //pridam cisty riadok do tabulky
        $.ajax({
            url: "ajax/addRowFamily.php",
            data: "type=" + type,
            type: "post",
            success: function(html) {
                //alert(html);
                window.location.reload();
            }
        });
    }
});