$("#file").change(function() {
    //prebehne kontrola filov
    var allowedFiles = [".PDF", ".pdf", ".doc", ".docs", ".docx", ".DOC", ".DOCS", ".DOCX"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var lblError = $("#error");
          
    var names = [];
    
    for (var i = 0; i < $(this).get(0).files.length; ++i) {
        //names.push($(this).get(0).files[i].name);
        //alert($(this).get(0).files[i].name);
        
        //kontrola koncovky
        if (!regex.test($(this).get(0).files[i].name.toLowerCase())) {
            lblError.html("Povolené sú iba tieto koncovky: <b>" + allowedFiles.join(', ') + "</b>");
            $( "#file" ).val("");
            return false;
        }        

        //kontrola velkosti
        if ($(this).get(0).files[i].size > 20971520) {
            
            lblError.html("Maximálna veľkosť súboru je: <b>20 MB</b>");
            $( "#file" ).val("");
            return false;
        }
        

    }
    lblError.empty();
    
    var path = $( this ).data( "path" );
   
    var data = new FormData();
    jQuery.each(jQuery('#file')[0].files, function(i, file) {
        data.append('file-'+i, file);
    });    
    
    
    request = $.ajax({
        url: "ajax/uploadFile.php?path=" + path,
        data: data, 
        cache: false,
        contentType: false,
        processData: false,        
        type: "post",
        success: function(data) {     
            alert(data);
            //window.location.reload();
            //$(" #help ").html(html);
            //alert("Súbor bol nahratý na server.");
        }
    });    
    
    
    return true;    
  

});



$("#fileAll").change(function() {
    //prebehne kontrola filov
    var allowedFiles = [".pdf", ".doc", ".docs", ".docx", ".PDF", ".DOC", ".DOCS", ".DOCX"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var lblError = $("#error");
          
    var names = [];
    for (var i = 0; i < $(this).get(0).files.length; ++i) {
        //names.push($(this).get(0).files[i].name);
        //alert($(this).get(0).files[i].name);
        
        //kontrola koncovky
        if (!regex.test($(this).get(0).files[i].name.toLowerCase())) {
            lblError.html("Povolené sú iba tieto koncovky: <b>" + allowedFiles.join(', ') + "</b>");
            $( "#fileAll" ).val("");
            return false;
        }        

        //kontrola velkosti
        if ($(this).get(0).files[i].size > 20971520) {
            
            lblError.html("Maximálna veľkosť súboru je: <b>20 MB</b>");
            $( "#fileAll" ).val("");
            return false;
        }
    }
    
    lblError.empty();
    
    var path = $( this ).data( "path" );
    var userId = $( this ).data( "user" );
   
    var data = new FormData();
    jQuery.each(jQuery('#fileAll')[0].files, function(i, file) {
        data.append('file-'+i, file);
    });    
    

    
    request = $.ajax({
        url: "ajax/uploadFile.php?path=" + path + "&user=" + userId,
        data: data, 
        cache: false,
        contentType: false,
        processData: false,        
        type: "post",
        success: function(data) {     
            //alert(data);
            
            //$(" #help ").html(html);
            alert("Súbor bol nahratý na server.");
            window.location.reload();
        }
    });    
    
    
    return true;    
  

});

$( ".removeDocument" ).click(function() {
    var id = $( this ).data("id");


    if (confirm("Naozaj chceš dokument odstrániť?")) {
        request = $.ajax({
            url: "ajax/hideDocument.php",
            data: "id=" + id, 
            type: "post",
            success: function(html) { 

                alert("Dokument bol odstránený.");
                window.location.reload();                
            }
        }); 
    }


});