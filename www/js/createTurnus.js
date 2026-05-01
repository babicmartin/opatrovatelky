$( ".createTurnus" ).click(function() {
    var userId = $( this ).data("user");
    var babysitterId = $( this ).data("babysitter");


    request = $.ajax({
        url: "ajax/createTurnus.php",
        data: "babysitterId=" + babysitterId + "&userId=" + userId, 
        type: "post",
        success: function(html) {     

            window.location.reload();
            //$(" #help ").html(html);
            //alert(html);
        }
    });
});    

$( ".createTurnusFamily" ).click(function() {
    var userId = $( this ).data("user");
    var familyId = $( this ).data("family");


    request = $.ajax({
        url: "ajax/createTurnusFamily.php",
        data: "familyId=" + familyId + "&userId=" + userId, 
        type: "post",
        success: function(html) {     

            window.location.reload();
            //$(" #help ").html(html);
            //alert(html);
        }
    });
});    





