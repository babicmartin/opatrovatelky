
$( ".updateCheckbox" ).click(function() {
    var id = $( this ).data("id");
    var table = $( this ).data("table");
    
    var val;
    if ($( this ).is(":checked")) {
        val = 1;
    } else {
        val = 0;
    }
    var column = $( this ).data("column");

    request = $.ajax({
        url: "ajax/updateCrud.php",
        data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val,
        type: "post",
        success: function(data) {     
            window.location.reload();
            //alert(data);
        }
    });
});    


$( ".updateCheckboxNotReload" ).click(function() {

    var id = $( this ).data("id");
    var table = $( this ).data("table");
    
    var val;
    if ($( this ).is(":checked")) {
        val = 1;
    } else {
        val = 0;
    }
    var column = $( this ).data("column");
    var type = $( this ).data("type");

    request = $.ajax({
        url: "ajax/updateCheckbox.php",
        data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val + "&type=" + type,
        type: "post",
        success: function(data) {     
            //window.location.reload();
            //alert(data);
        }
    });
});  



$( ".updateRadio" ).click(function() {

    var id = $( this ).data("id");
    var table = $( this ).data("table");
    
    var val = $( this ).val();
    
    var column = $( this ).data("column");
    var type = $( this ).data("type");

    request = $.ajax({
        url: "ajax/updateCheckbox.php",
        data: "id=" + id + "&table=" + table + "&column=" + column + "&val=" + val + "&type=" + type,
        type: "post",
        success: function(data) {     
           window.location.reload();
            //alert(data);
        }
    });
});   


$( ".addOrRemoveCheckbox" ).click(function() {

    var id = $( this ).data("id");
    var babysitterId = $( this ).data("babysitter");
    var diseaseId = $( this ).data("disease");
    var table = $( this ).data("table");

    request = $.ajax({
        url: "ajax/addRemoveCheckbox.php",
        data: "id=" + id + "&babysitterId=" + babysitterId + "&diseaseId=" + diseaseId + "&table=" + table,
        type: "post",
        success: function(data) {     
            //window.location.reload();
            //alert(data);
        }
    });
});

$( ".addOrRemoveCheckboxQualificationPreference" ).click(function() {

    var id = $( this ).data("id");
    var babysitterId = $( this ).data("babysitter");
    var positionId = $( this ).data("position");
    var table = $( this ).data("table");

    request = $.ajax({
        url: "ajax/addRemoveCheckboxQualificationPreference.php",
        data: "id=" + id + "&babysitterId=" + babysitterId + "&positionId=" + positionId + "&table=" + table,
        type: "post",
        success: function(data) {
            //window.location.reload();
            //alert(data);
        }
    });
});

