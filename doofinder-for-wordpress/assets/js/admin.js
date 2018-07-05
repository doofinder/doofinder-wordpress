jQuery(function () {
    var $ = jQuery.noConflict();
    var ProgressBar = (function () {
        function ProgressBar(bar) {
            this.bar = bar.querySelector('[data-bar]');
        }
        ProgressBar.prototype.set = function (value) {
            if (value < 0) {
                value = 0;
            }
            if (value > 100) {
                value = 100;
            }
            this.bar.style.width = value + "%";
        };
        return ProgressBar;
    }());
    var button = document.getElementById('doofinder-for-wp-index-button');
    var spinner = document.getElementById('doofinder-for-wp-spinner');
    var progressBarElement = document.getElementById('doofinder-for-wp-progress-bar');
    var progressBarStatusElement = document.getElementById('doofinder-for-wp-progress-bar-status');
    var additionalMessagesElement = document.getElementById('doofinder-for-wp-additional-messages');
    var indexingError = document.getElementById('doofinder-for-wp-indexing-error');
    if (!button) {
        return;
    }
    var progressBar = new ProgressBar(progressBarElement);
    var maxRetries = 3;
    var errorCount = 0;
    var maxTimeout = 5 * 60 * 1000;
    var currentTimeout = 0;
    var indexContentTimeout = 5000;
    var preparing = true;
    var ajaxIndexContent = function () {
        if (preparing) {
            setProgressBarStatus(false, true);
        }
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: DoofinderForWP.ajaxUrl,
            data: {
                action: 'doofinder_for_wp_index_content'
            }
        })
            .then(function (response) {
            if (!response.success) {
                handleError(response);
                return;
            }
            errorCount = 0;
            currentTimeout = 0;
            if ('progress' in response.data) {
                updateProgressBar(response);
            }
            if ('message' in response.data) {
                showAdditionalMessages(response);
            }
            if (!response.data.completed) {
                ajaxIndexContent();
                return;
            }
            confirmLeavePage(false);
            setMessageCookie();
            window.location.reload();
        });
    };
    var handleError = function (response) {
        if (response.data && response.data.status === 'indexing_in_progress') {
            currentTimeout += indexContentTimeout;
        }
        else {
            errorCount++;
        }
        if ('message' in response.data) {
            showAdditionalMessages(response);
        }
        if (errorCount > maxRetries || currentTimeout > maxTimeout) {
            indexingError.classList.add('active');
            button.disabled = false;
            spinner.style.display = '';
            spinner.style.visibility = '';
            errorCount = 0;
            currentTimeout = 0;
            setProgressBarStatus();
            preparing = true;
            confirmLeavePage(false);
        }
        else {
            setTimeout(function () {
                ajaxIndexContent();
            }, indexContentTimeout);
        }
    };
    var updateProgressBar = function (response) {
        progressBar.set(response.data.progress);
        setProgressBarStatus(true);
        preparing = false;
    };
    var showAdditionalMessages = function (response) {
        additionalMessagesElement.classList.add('active');
        additionalMessagesElement.innerText = response.data.message;
    };
    var setProgressBarStatus = function (indexing, preparing) {
        if (indexing === void 0) { indexing = false; }
        if (preparing === void 0) { preparing = false; }
        var indexingStatus = progressBarStatusElement.querySelector('.indexing');
        var preparingStatus = progressBarStatusElement.querySelector('.preparing');
        if (indexing) {
            indexingStatus.classList.add('active');
        }
        else {
            indexingStatus.classList.remove('active');
        }
        if (preparing) {
            preparingStatus.classList.add('active');
        }
        else {
            preparingStatus.classList.remove('active');
        }
    };
    var confirmLeavePage = function (active) {
        if (active === void 0) { active = true; }
        if (active) {
            window.onbeforeunload = function () { return ''; };
        }
        else {
            window.onbeforeunload = function () { return null; };
        }
    };
    var setMessageCookie = function () {
        document.cookie = 'doofinder_wp_show_success_message=true';
    };
    var initAjaxIndexContent = function () {
        $(button).click(function () {
            indexingError.classList.remove('active');
            button.disabled = true;
            spinner.style.display = 'inline-block';
            spinner.style.visibility = 'visible';
            confirmLeavePage();
            ajaxIndexContent();
        });
    };
    initAjaxIndexContent();
});
jQuery(function () {
    var $ = jQuery.noConflict();
    var cancelButton = document.querySelector('#doofinder-for-wp-cancel-indexing');
    if (!cancelButton) {
        return;
    }
    cancelButton.addEventListener('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: DoofinderForWP.ajaxUrl,
            data: {
                action: 'doofider_for_wp_cancel_indexing'
            }
        })
            .then(function () {
            window.location.reload();
        });
    });
});
