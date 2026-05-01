$( ".uploadImage" ).click(function() {


    var update = false;
    var dir = $( this ).data("path");
    var id = $( this ).data("id");
    var updateHelp = $( this ).data("update");
    if (updateHelp == 1) updateHelp = true;
    
    
    var table = $( this ).data("table");
    var path = 'web/img/' + dir + '/';


    
    
    //otvorim si vyber suborov
    $("input").click();
    
    
    $('input[type=file]').change(function() {
    
        var test = $( this )[0].files[0]; 


        var formData = new FormData();
        formData.append('image', test); 
        formData.append('id', id); 
        formData.append('path', path); 
        formData.append('table', table); 
        formData.append('update', update); 

        //alert(formData.image);


        //teraz by som to mal nejako preniest
        $.ajax({
                url: "ajax/uploadFileJQuery.php",
                data: formData, 
                type: "post",
                contentType: false, // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
                processData: false, // NEEDED, DON'T OMIT THIS            
                success: function(html) {
                    //alert(html);
                    window.location.reload();
                }
            }); 


    });

});
