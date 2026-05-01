$( ".createProposalFamily" ).click(function() {
    var userId = $( this ).data("user");
    var familyId = $( this ).data("family");

    if (confirm("Naozaj chceš vytvoriť návrh pre rodinu?")) {
        request = $.ajax({
            url: "ajax/createProposalFamily.php",
            data: "familyId=" + familyId + "&userId=" + userId, 
            type: "post",
            success: function(html) {     

                window.location.reload();
                //$(" #help ").html(html);
                //alert(html);
            }
        });
    }
});    





