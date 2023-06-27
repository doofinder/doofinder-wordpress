jQuery(function () {
    let $ = jQuery.noConflict();
    let indexingCheckInterval = null;
    let ajaxIndexingStatus = function () {
        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: "doofinder_check_indexing_status",
            },
            success: function (response) {
                if (response.status === "processed") {
                    $(".indexation-status").toggleClass("processing processed");
                    clearInterval(indexingCheckInterval);
                }
            },
        });
    };

    if (Doofinder.show_indexing_notice === "true") {
        indexingCheckInterval = setInterval(ajaxIndexingStatus, 10000);
    }

    /*
    TODO: Implement notice dismiss ajax action

    $(".notice.is-dismissable .notice-dismissible").on(
        "click",
        function () {
            let notice_id = $(this).attr("id");
            console.log("calling dismiss notice");
            $.ajax({
                type: "post",
                dataType: "json",
                url: ajaxurl,
                data: {
                    action: "doofinder_dismiss_notice",
                    notice_id: notice_id
                },
                success: function (response) {
                    console.log("Notice dismissed")
                },
            });
        }
    );
    */
});
