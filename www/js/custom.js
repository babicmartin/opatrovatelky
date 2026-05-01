//function repion() {
  /*
$( 'document' ).ready(function() {
    let ionicons = document.querySelectorAll('ion-icon');
    ionicons.forEach(icon => {
        let properTitle = icon.getAttribute('title');
        //alert(properTitle);
        if (properTitle) {
            icon.shadowRoot.querySelector('.icon-inner svg title').innerHTML = properTitle
        }
    });
});
*/
//}



$( ".removeTurnus" ).click(function() {
    var id = $( this ).data("id");

    if (confirm("Naozaj chceš odstrániť turnus?")) {
        request = $.ajax({
            url: "ajax/hideTurnus.php",
            data: "id=" + id + "&table=sn_turnus", 
            type: "post",
            success: function(html) { 

                alert("Turnus bol odstránený.");
                window.location.assign('http://databaza.servis4ms.sk/?page=turnus');                
            }
        }); 
    }
});

$( ".removeMissingRegistry" ).click(function() {
    var id = $( this ).data("id");

    if (confirm("Naozaj chceš odstrániť evidenciu neprítomnosti?")) {
        request = $.ajax({
            url: "ajax/hideMissingRegistry.php",
            data: "id=" + id + "&table=sn_missing_registry",
            type: "post",
            success: function(html) {
                alert("Evidencia bola odstránená.");
                window.location.reload();
            }
        });
    }
});

$( ".removeItem" ).click(function() {
    var id = $( this ).data("id");
    var table = $( this ).data("table");
    var page = $( this ).data("page");

    if (confirm("Naozaj chceš odstrániť záznam?")) {
        request = $.ajax({
            url: "ajax/hide.php",
            data: "id=" + id + "&table=" + table,
            type: "post",
            success: function(html) {
                alert("Záznam bol odstránený.");

                window.location.assign('http://databaza.servis4ms.sk/?page=' + page);
            }
        });
    }
});