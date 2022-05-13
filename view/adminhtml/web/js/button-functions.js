require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'prototype',
    'loader'
], function ($, modal) {

    /**
     * @param{String} modalSelector - modal css selector.
     * @param{Object} options - modal options.
     */
    function initModal(modalSelector, options) {
        var $resultModal = $(modalSelector);

        if (!$resultModal.length) return;

        var popup = modal(options, $resultModal);
        $resultModal.loader({texts: ''});
    }

    var successHandlers = {
        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        debug: function (result, $container) {

            if (Array.isArray(result)) {

                var lisHtml = result.map(function (err) {
                    return '<li class="mm-channable-result_debug-item"><strong>' + err.date + '</strong><p>' + err.msg + '</p></li>';
                }).join('');

                $container.find('.result').empty().append('<ul>' + lisHtml + '</ul>');
            } else {

                $container.find('.result').empty().append(result);
            }
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        error: function (result, $container) {

            if (Array.isArray(result)) {

                var lisHtml = result.map(function (err) {
                    return '<li class="mm-channable-result_error-item"><strong>' + err.date + '</strong><p>' + err.msg + '</p></li>';
                }).join('');

                $container.find('.result').empty().append('<ul>' + lisHtml + '</ul>');
            } else {

                $container.find('.result').empty().append(result);
            }
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        test: function (result, $container) {

            var lisHtml = result.map(function (test) {

                var supportLinkHtml = test.support_link ? '<a target="_blank" href="' + test.support_link + '" class="mm-channable-icon__help-rounded"></a>' : '';
                var resultText = test.result_code === 'success' ? $.mage.__('Passed') : $.mage.__('Failed');
                var resultMsg = test.result_msg;

                return '<li class="mm-channable-result_test-item ' + test.result_code
                    + '"><strong>' + resultText + '</strong>'
                    + '<div><p>' + test.test + ':</p><p><em>' + resultMsg + '</em></p></div>'
                    + supportLinkHtml + '</li>';
            }).join('');

            $container.find('.result').empty().append(lisHtml);
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        version: function (result, $container) {

            var resultHtml = '';
            var currentVersion = result.current_verion.replace(/v|version/gi, '');
            var latestVersion = result.last_version.replace(/v|version/gi, '');

            if (this.compare(latestVersion, currentVersion) <= 0) {
                resultHtml = '<strong class="mm-channable-version mm-channable-icon__thumbs-up">'
                    + $.mage.__('Great, you are using the latest version.')
                    + '</strong>';
            } else {

                var translatedResult = $.mage.__('There is a new version available <span>(%1)</span> see <button type="button" id="mm-channable-button_changelog">changelog</button>.')
                    .replace('%1', latestVersion);

                resultHtml = '<strong class="mm-channable-version mm-channable-icon__thumbs-down">'
                    + translatedResult
                    + '</strong>';
            }

            $container.html(resultHtml);
        },

        compare: function(a, b) {
            if (a === b) {
                return 0;
            }
            var a_components = a.split(".");
            var b_components = b.split(".");
            var len = Math.min(a_components.length, b_components.length);
            for (var i = 0; i < len; i++) {
                if (parseInt(a_components[i]) > parseInt(b_components[i])) {
                    return 1;
                }

                if (parseInt(a_components[i]) < parseInt(b_components[i])) {
                    return -1;
                }
            }
            if (a_components.length > b_components.length) {
                return 1;
            }
            if (a_components.length < b_components.length) {
                return -1;
            }
            return 0;
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        changelog: function (result, $container) {

            var lisHtml = Object.keys(result).map(function (key) {

                var version = key;
                var date = result[key].date;
                var resultHtml = result[key].changelog;

                return '<li class="mm-channable-result_changelog-item"><b>'
                    + version + '</b><span class="mm-channable-divider">|</span><b>'
                    + date + '</b><div>'
                    + resultHtml + '</div></li>';
            }).join('');

            $container.find('.result').empty().append(lisHtml);
        },
    }

    // init debug modal
    $(() => {
        initModal('#mm-channable-result_debug-modal', {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $.mage.__('last 100 debug log lines'),
            buttons: [
                {
                    text: $.mage.__('download as .txt file'),
                    class: 'mm-channable-button__download mm-channable-icon__download-alt',
                    click: function () {

                        var elText = document.getElementById('mm-channable-result_debug').innerText || '';
                        var link = document.createElement('a');

                        link.setAttribute('download', 'debug-log.txt');
                        link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                        link.click();
                    },
                },
                {
                    text: $.mage.__('ok'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        });

        // init error modal
        initModal('#mm-channable-result_error-modal', {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $.mage.__('last 100 error log records'),
            buttons: [
                {
                    text: $.mage.__('download as .txt file'),
                    class: 'mm-channable-button__download mm-channable-icon__download-alt',
                    click: function () {

                        var elText = document.getElementById('mm-channable-result_error').innerText || '';
                        var link = document.createElement('a');

                        link.setAttribute('download', 'error-log.txt');
                        link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                        link.click();
                    },
                },
                {
                    text: $.mage.__('ok'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        });

        // init selftest modal
        initModal('#mm-channable-result_test-modal', {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $.mage.__('Self-test'),
            buttons: [
                {
                    text: $.mage.__('ok'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        });

        // init changelog modal
        initModal('#mm-channable-result_changelog-modal', {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Changelog',
            buttons: [
                {
                    text: $.mage.__('ok'),
                    class: '',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        });
    });
    // init loader on the Check Version block
    $('.mm-channable-result_version-wrapper').loader({texts: ''});

    /**
     * Ajax request event
     */
    $(document).on('click', '[id^=mm-channable-button]', function () {
        var actionName = this.id.split('_')[1];
        var $modal = $('#mm-channable-result_' + actionName + '-modal');
        var $result = $('#mm-channable-result_' + actionName);

        if (actionName === 'version') {
            $(this).fadeOut(300).addClass('mm-channable-disabled');
            $modal = $('.mm-channable-result_' + actionName + '-wrapper');
            $modal.loader('show');
        } else {
            $modal.modal('openModal').loader('show');
        }

        $result.hide();

        new Ajax.Request($modal.data('mm-channable-endpoind-url'), {
            loaderArea: false,
            asynchronous: true,
            onSuccess: function (response) {

                if (response.status > 200) {
                    var result = response.statusText;
                } else {
                    successHandlers[actionName](response.responseJSON.result || response.responseJSON, $result);

                    $result.fadeIn();
                    $modal.loader('hide');
                }
            }
        });
    });
});
