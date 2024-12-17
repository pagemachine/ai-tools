import { callAjaxCreditsAction } from './utils/RemoteCalls.js';


class CreditsViewHelper {
  constructor() {
    this.initialize();
  }

  async initialize() {
    const elements = document.querySelectorAll('.t3js-ai-tools-credits-view-helper');
    await this.initializeElements(elements);
  }

  async initializeElements(elements) {
    for (let element of elements) {
      await this.initializeElement(element);
    }
  }

  async initializeElement(element) {
    const data = {
      type: element.getAttribute('data-type'),
      fileIdentifier: element.getAttribute('data-file-identifier'),
      targetLanguage: element.getAttribute('data-target-language'),
      textPrompt: element.getAttribute('data-text-prompt'),
    };

    if (data.type) {
      return callAjaxCreditsAction(data).then(response => {
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
}

export default new CreditsViewHelper();
