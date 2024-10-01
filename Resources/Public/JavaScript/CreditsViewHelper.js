/**
 * Module: TYPO3/CMS/AiTools/CreditsViewHelper
 *
 * @exports TYPO3/CMS/AiTools/CreditsViewHelper
 */
define(['jquery', 'TYPO3/CMS/AiTools/RemoteCalls'], function ($, RemoteCalls) {
  'use strict';

  return function (url) {
    $(async () => {
      const elements = document.querySelectorAll('.t3js-ai-tools-credits-view-helper');

      for(let element of elements) {
        const data = {
          type: element.getAttribute('data-type'),
          fileIdentifier: element.getAttribute('data-file-identifier'),
          targetLanguage: element.getAttribute('data-target-language'),
          textPrompt: element.getAttribute('data-text-prompt'),
        };

        console.log(data);

        if (data.type) {
          await RemoteCalls.callAjaxCreditsAction(url, data).then(response => {
            console.log('Credits', response);
            if (response.credits) {
              element.innerHTML = response.credits;
              element.style = '';
            }
          }).catch(error => {
            console.error('Credits', error);
          });
        }
      }
    });
  };
});
