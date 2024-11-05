/**
 * Module: TYPO3/CMS/AiTools/CreditsViewHelper
 *
 * @exports TYPO3/CMS/AiTools/CreditsViewHelper
 */
define(['jquery', 'TYPO3/CMS/AiTools/RemoteCalls'], function ($, RemoteCalls) {
  'use strict';

  return function (url, selector) {
    $(async () => {
      const elements = document.querySelectorAll(selector);

      for(let element of elements) {
        const data = {
          type: element.getAttribute('data-type'),
          fileIdentifier: element.getAttribute('data-file-identifier'),
          targetLanguage: element.getAttribute('data-target-language'),
          textPrompt: element.getAttribute('data-text-prompt'),
        };

        if (data.type) {
          await RemoteCalls.callAjaxCreditsAction(url, data).then(response => {
            if (response.credits) {
              element.innerHTML = response.credits + ' Credits';
              element.setAttribute('data-credits', response.credits);
              element.style = '';

              const event = new CustomEvent('creditsUpdate', {
                detail: response.credits
              });
              document.dispatchEvent(event);
            }
          }).catch(error => {
            console.error('Credits', error);
          });
        }
      }
    });
  };
});
