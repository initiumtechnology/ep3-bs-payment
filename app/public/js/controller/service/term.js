(function() {

    $(document).ready(function() {

        var termPanel = $("#term-panel");

        if (termPanel.length) {
            termPanel.find("img").closest("a").css("opacity", 1.0).wrap('<div class="panel">');
        }

    });

})();