require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'loader'
], function ($, modal) {
    'use strict';

    const moduleName = 'channable';

    /**
     * @param{String} modalSelector - modal css selector.
     * @param{Object} options - modal options.
     */
    function initModal(type, options) {
        const modalId = `#mm-ui-result_${type}-modal`;
        if (!$(modalId).length) return;
        
        let defaultOptions = {
            modalClass: `mm-ui-modal ${moduleName}`,
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: options.title || '',
            buttons: [
                {
                    text: $.mage.__('Close window'),
                    class: 'mm-ui-modal-close',
                    click: function () {
                        this.closeModal();
                    },
                }
            ]
        };

        // Additional buttons for downloading
        if (options.buttons) {
            let additionalButtons = {
                text: $.mage.__('download as .txt file'),
                class: 'mm-ui-button__download mm-ui-icon__download-alt',
                click: () => {
                    let elText = document.getElementById(`mm-ui-result_${options.buttons}`).innerText || '',
                        link = document.createElement('a');

                    link.setAttribute('download', `${options.buttons}-log.txt`);
                    link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(elText));
                    link.click();
                },
            };

            defaultOptions.buttons.unshift(additionalButtons);
        }

        modal(defaultOptions, $(modalId));
        $(modalId).loader({texts: ''});
    }

    var successHandlers = {
        /**
         * @param{Object[]} obj - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         * @param{String} type - debug || error || test.
         */
        logs(response, $container, type) {
            // If source blocked
            if (response.error) response = this.tmpBlockedSource(response.message);

            if (Array.isArray(response.result)) {
                if (type === 'debug' || type === 'error') {
                    response = `<ul>${response.result.map((data) => this.tmpLogs(type, data)).join('')}</ul>`;                              
                }

                if (type === 'test') {
                    response = `${response.result.map((data) => this.tmpTest(type, data)).join('')}`; 
                }
            }

            $container.find('.result').empty().append(response);
        },

        tmpLogs(type, data) {
            return `<li class="mm-ui-result_${type}-item">
                        <strong>${data.date}</strong>
                        <p>${data.msg}</p>
                    </li>`;
        },

        tmpTest(type, data) {
            let supportLinkHtml = '',
                resultMsg = data.result_code === 'failed' ? data.result_msg : '',
                resultText = data.result_code === 'success' 
                                ? $.mage.__('Passed') 
                                : $.mage.__('Failed'),

                svg = data.result_code === 'success' 
                        ? ` <svg xmlns="http://www.w3.org/2000/svg" width="27.5" height="27.5" viewBox="0 0 27.5 27.5">
                                <path d="M28.063,14.313A13.75,13.75,0,1,1,14.313.563,13.75,13.75,0,0,1,28.063,14.313Zm-15.34,7.281,10.2-10.2a.887.887,0,0,0,0-1.255L21.669,8.882a.887.887,0,0,0-1.255,0l-8.32,8.32L8.21,13.318a.887.887,0,0,0-1.255,0L5.7,14.572a.887.887,0,0,0,0,1.255l5.766,5.766a.887.887,0,0,0,1.255,0Z" transform="translate(-0.563 -0.563)" fill="#109cd8"/>
                            </svg>`
                        :   `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30">
                                <path d="M18,3A15,15,0,1,0,33,18,15.005,15.005,0,0,0,18,3Zm1.5,22.5h-3v-3h3Zm0-6h-3v-9h3Z" transform="translate(-3 -3)" fill="#e33b8f"/>
                            </svg>`;

            if (data.support_link) {
                supportLinkHtml = `<a target="_blank" href="${data.support_link}"
                                      class="mm-ui-icon__help-rounded">
                                        ${$.mage.__('More information')}

                                        <svg xmlns="http://www.w3.org/2000/svg" width="20.019" height="20.019" viewBox="0 0 20.019 20.019">
                                            <path d="M20.019,4.395V20.124a2.145,2.145,0,0,1-2.145,2.145H2.145A2.145,2.145,0,0,1,0,20.124V4.395A2.145,2.145,0,0,1,2.145,2.25H17.874A2.145,2.145,0,0,1,20.019,4.395Zm-3.932.715h-5a1.073,1.073,0,0,0-.758,1.831L11.754,8.37,3.017,17.107a.536.536,0,0,0,0,.758L4.4,19.252a.536.536,0,0,0,.758,0L13.9,10.515l1.429,1.429a1.073,1.073,0,0,0,1.831-.758v-5A1.072,1.072,0,0,0,16.086,5.11Z" transform="translate(0 -2.25)" fill="#f8f8f8"/>
                                        </svg>
                                    </a>`;
            }

            return `<li class="mm-ui-result_${type}-item ${data.result_code}">
                        ${svg}
                        <div>
                            <strong>${resultText}</strong>
                            <div>
                                <p>${data.test}</p>
                                <p><em>${resultMsg}</em></p>
                            </div>
                            ${supportLinkHtml}
                        </div>
                    </li>`;
        },

        tmpBlockedSource(message) {
            return `<div>
                        <div class="mm-ui-source-blocked">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                            </svg>
                            <span>${message}</span>       
                        </div>
                    </div>`;
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        version(response, $container) {
            let resultHTML = '';
            
            if (response.error) {
                // If source blocked
                resultHTML = this.tmpBlockedSource(response.message);
            } else {
                let resultText = $.mage.__('Great, you are using the latest version.'),
                    resultClass = 'up';

                if (this.isLastVersion(response.result.current_version, response.result.last_version)) {
                    resultClass = 'down';
                    resultText = $.mage.__('There is a new version available <span>(%1)</span> see <button type="button" id="mm-ui-button_changelog">changelog</button>.')
                        .replace('%1', response.result.last_version.slice(1));
                }

                resultHTML = `<strong class="mm-ui-version mm-ui-icon__thumbs-${resultClass}">
                                ${resultText}
                            </strong>`;
            }

            $container.html(resultHTML);
        },

        /**
         * @param{String} - current module version
         * @param{String} - last module version
         * @returns {Boolean} 
         */
        isLastVersion(v1, v2) {
            let array1 = v1.slice(1).split('.').map((i) => +i),
                array2 = v2.slice(1).split('.').map((i) => +i);

            for(let i = 0; i < array1.length; i++) {
                if (array1[i] !== array2[i]) return array1[i] < array2[i] ? true : false;
            }

            return false;
        },

        /**
         * @param{Object[]} result - Ajax request response data.
         * @param{Object} $container - jQuery container element.
         */
        changelog(response, $container) {
            let listHTML = Object.keys(response).map((version) => {
                return `<li class="mm-ui-result_changelog-item">
                            <b>${version}</b>
                            <span class="mm-ui-divider">|</span>
                            <b>${response[version].date}</b>
                            <div>${response[version].changelog}</div>
                        </li>`;
            });

            $container.find('.result').empty().append(listHTML.join(''));
        },
    }

    // init modals
    $(() => {
        initModal('debug',      { title: $.mage.__('Last 100 debug log records'), buttons: 'debug' });
        initModal('error',      { title: $.mage.__('Last 100 error log records'), buttons: 'error' });
        initModal('test',       { title: $.mage.__('Self-test') });
        initModal('changelog',  { title: $.mage.__('Changelog') });
    });
    
    // init loader on the Check Version block
    $('.mm-ui-result_version-wrapper').loader({texts: ''});

    /**
     * Ajax request event
     */
    $(document).on('click', '[id^=mm-ui-button]', function () {
        let action = this.id.split('_')[1],
            $modal = $(`#mm-ui-result_${action}-modal`),
            $result = $(`#mm-ui-result_${action}`);

        const func = action === 'debug' || 
                     action === 'error' || 
                     action === 'test' ? 'logs' : action;

        if (action === 'version') {
            $(this).fadeOut(300).addClass('mm-ui-disabled');
            $modal = $(`.mm-ui-result_${action}-wrapper`);
            $modal.loader('show');
        } else {
            $modal.modal('openModal').loader('show');
        }

        $result.hide();

        fetch($modal.attr('data-mm-ui-endpoind-url'))
            .then((res) => res.clone().json().catch(() => res.text()))
            .then((data) => {
                successHandlers[func](data, $result, action);
            })
            .catch(() => {
                // If response block with adBlock extensions
                const error = {
                    error: true,
                    message: $.mage.__("Failed to load data. Disable the ad blocker. If it didn't help, please contact us."),
                };

                successHandlers[func](error, $result, action);
            })
            .finally(() => {
                $result.fadeIn();
                $modal.loader('hide');
            });
    });
});
