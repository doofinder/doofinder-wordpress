jQuery(function () {
    let $ = jQuery.noConflict();
    let ajaxIndexingStatus = function () {
        console.log("Checking indexing status");
        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: "doofinder_check_indexing_status",
            },
            success: function (response) {
                if (response.status === "processed") {
                    $(".indexation-status").toggleClass("processing processed")
                    clearInterval(indexingCheckInterval);
                }
            },
        });
    };
    let indexingCheckInterval = setInterval(ajaxIndexingStatus, 10000);
});
