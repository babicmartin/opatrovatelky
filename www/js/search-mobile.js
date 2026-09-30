$(function() {
    var overlay = $("#mobileSearch");
    if (!overlay.length) {
        return;
    }

    var input = overlay.find(".msearch-input");
    var results = overlay.find(".msearch-results");
    var hint = overlay.find(".msearch-hint");
    var clearButton = overlay.find(".msearch-clear");
    var url = overlay.data("search-url");
    var typeParam = overlay.data("type-param") || "type";
    var termParam = overlay.data("term-param") || "term";

    var TERM_KEY = "msearch.term";
    var TYPE_KEY = "msearch.type";
    var MIN_LENGTH = 3;

    var request = null;
    var timeout = null;
    var currentType = storageGet(localStorage, TYPE_KEY) || "";
    var countsTerm = null;

    function storageGet(storage, key) {
        try {
            return storage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function storageSet(storage, key, value) {
        try {
            storage.setItem(key, value);
        } catch (e) {
            // storage unavailable (private mode) – state just won't persist
        }
    }

    function isOpen() {
        return !overlay.prop("hidden");
    }

    function show() {
        overlay.prop("hidden", false);
        $("body").addClass("msearch-open");
        setType(currentType, false);

        var term = storageGet(sessionStorage, TERM_KEY) || "";
        if (input.val() === "" && term !== "") {
            input.val(term);
            search();
        }
        updateClear();
        input.trigger("focus");
    }

    function hide() {
        overlay.prop("hidden", true);
        $("body").removeClass("msearch-open");
        input.trigger("blur");
    }

    function open() {
        if (isOpen()) {
            return;
        }
        history.pushState({ msearch: true }, "");
        show();
    }

    function close() {
        if (history.state && history.state.msearch) {
            history.back(); // popstate hides the overlay
        } else {
            hide();
        }
    }

    function updateClear() {
        clearButton.prop("hidden", input.val() === "");
    }

    function setCounts(counts) {
        overlay.find(".msearch-chip[data-type!='']").each(function() {
            var chip = $(this);
            var count = counts ? counts[chip.data("type")] : undefined;
            chip.find(".msearch-count").text(count === undefined ? "" : count);
            chip.toggleClass("empty", count === 0);
        });
    }

    function setType(type, runSearch) {
        currentType = String(type);
        storageSet(localStorage, TYPE_KEY, currentType);
        overlay.find(".msearch-chip").each(function() {
            var chip = $(this);
            chip.toggleClass("active", String(chip.data("type")) === currentType);
        });

        var active = overlay.find(".msearch-chip.active")[0];
        if (active) {
            active.scrollIntoView({ block: "nearest", inline: "center" });
        }
        if (runSearch) {
            search();
        }
    }

    function search() {
        var term = $.trim(input.val());
        storageSet(sessionStorage, TERM_KEY, term);
        clearTimeout(timeout);
        if (request) {
            request.abort();
            request = null;
        }

        if (term.length < MIN_LENGTH) {
            overlay.removeClass("loading");
            results.empty();
            hint.show();
            setCounts(null);
            countsTerm = null;
            return;
        }

        hint.hide();
        overlay.addClass("loading");

        var payload = {};
        payload[termParam] = term;
        if (currentType !== "") {
            payload[typeParam] = currentType;
        }

        request = $.ajax({
            url: url,
            data: payload,
            type: "post",
            success: function(response) {
                results.html(response.html || "");
                overlay.find(".msearch-body").scrollTop(0);

                if (currentType === "") {
                    setCounts(response.counts);
                    countsTerm = term;
                } else if (countsTerm !== term) {
                    setCounts(response.counts); // counts of other types are unknown for this term
                    countsTerm = null;
                }
            },
            complete: function(xhr, status) {
                if (status !== "abort") {
                    overlay.removeClass("loading");
                    request = null;
                }
            }
        });
    }

    $(document).on("click", "[data-msearch-open]", function() {
        open();
    });

    overlay.on("click", "[data-msearch-close]", close);

    overlay.on("click", ".msearch-chip", function() {
        setType($(this).data("type"), true);
    });

    overlay.on("click", ".msearch-more", function() {
        setType($(this).data("show-type"), true);
    });

    clearButton.on("click", function() {
        input.val("").trigger("focus");
        updateClear();
        search();
    });

    input.on("input", function() {
        updateClear();
        clearTimeout(timeout);
        timeout = setTimeout(search, 250);
    });

    input.on("keydown", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            search();
            input.trigger("blur"); // hide the keyboard so results are visible
        }
    });

    $(document).on("keydown", function(e) {
        if (e.key === "Escape" && isOpen()) {
            close();
        }
    });

    $(window).on("popstate", function() {
        var wantOpen = !!(history.state && history.state.msearch);
        if (wantOpen && !isOpen()) {
            show();
        } else if (!wantOpen && isOpen()) {
            hide();
        }
    });

    // Returning (Back) from a detail opened from the results – reopen with the last search
    if (history.state && history.state.msearch && window.matchMedia("(max-width: 1199.98px)").matches) {
        show();
        input.trigger("blur");
    }
});
