require([
    'jquery',
    'mage/translate',
    '!domReady'
], function ($, $t) {

    let comment = $('.mm-ui-heading-comment'),
        heading = comment.parent().find('.mm-ui-heading-block'),
        showMoreLessBtnHtml = `
            <div class="mm-ui-show-more-actions hidden">
                <a href="javascript:void(0)" class="mm-ui-show-btn-more">
                    ${$t('More')}
                </a>
            </div>`;

    // Additional check if tab was closed during initialization
    const parent = document.querySelector('form[action*="channable"]');

    if (parent) {
        const observer = new MutationObserver((mutation) => {
            for (let i = 0; i < mutation.length; i++) {
                if (mutation[i].target.classList.contains('section-config')) {
                    isShowMore();
                }
            }
        });
    
        observer.observe(parent, { 
            subtree: true,
            attributes: true,
            attributeFilter: ['class'],
        });
    
        if(comment.length) {
            heading.append(showMoreLessBtnHtml);
    
            $(document).on('click', '.mm-ui-show-more-actions a', (e) => {
                let button = $(e.target),
                    parent = button.closest('.value').find('.mm-ui-heading-comment');
    
                if (parent.hasClass('show')) {
                    parent.removeClass('show');
                    button.text($t('More'));
                } else {
                    parent.addClass('show');
                    button.text($t('Less'));
                }
            });
    
            $(document).ready(isShowMore);
            window.addEventListener("resize", isShowMore);
        }
    
        function isShowMore() {
            Array.from(comment).forEach((item) => {
                const BTN = item.closest('td').querySelector('.mm-ui-show-more-actions');
                item.scrollHeight <= 55 ? BTN.classList.add('hidden') : BTN.classList.remove('hidden');
            });
        }
    }
});
