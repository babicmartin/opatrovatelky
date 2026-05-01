$(function() {
    var request = null;
    var timeout = null;

    $(".search").on("keyup", function() {
        var input = $(this);
        var type = input.data("id");
        var term = input.val();
        var url = input.data("search-url");
        var typeParam = input.data("type-param") || "type";
        var termParam = input.data("term-param") || "term";

        if (term.length <= 2 || !url) {
            return;
        }

        clearTimeout(timeout);
        timeout = setTimeout(function() {
            if (request) {
                request.abort();
            }

            var payload = {};
            payload[typeParam] = type;
            payload[termParam] = term;

            request = $.ajax({
                url: url,
                data: payload,
                type: "post",
                success: function(response) {
                    $("#searchResults").show().html(response.html || "");
                }
            });
        }, 180);
    });
});
