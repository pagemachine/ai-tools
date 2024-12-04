import $ from 'jquery';
import Modal from '@typo3/backend/modal';
import { MessageUtility }  from '@typo3/backend/utility/message-utility';
import GeneratorButton from './utils/GeneratorButton.js';
import FormEngine from '@typo3/backend/form-engine.js';

class AlternativeGenerator {
  currentModal = null;
  triggerTarget = null;

  constructor() {
    this.initializeListener();
    this.initializeTrigger();
  }

  initializeListener() {
    window.addEventListener('message', (e) => {
      if (!MessageUtility.verifyOrigin(e.origin)) {
        throw 'Denied message sent by ' + e.origin;
      }

      if (e.data.actionName === 'typo3:aiTools:updateField') {
        if (typeof e.data.value === 'undefined') {
          throw 'value not defined in message';
        }

        if (this.currentModal) {
          this.triggerTarget.val(e.data.value);
          this.currentModal.hideModal();
        }
      }
    });
  }

  initializeTrigger() {
    const generator = new GeneratorButton();
    generator.updateHook = (target, results) => {
      if (typeof FormEngine !== 'undefined' && FormEngine.Validation) {
        FormEngine.Validation.markFieldAsChanged(target);
        FormEngine.Validation.validateField(target);
      }
    };
    $('.t3js-alternative-generator-settings-trigger').off('click').on('click', (e) => this.clickSettingsHandler(e));
  }

  clickSettingsHandler(event) {
    event.preventDefault();
    event.stopPropagation();
    this.show($(event.currentTarget));
  }

  show(trigger) {
    this.currentModal = Modal.advanced({
      additionalCssClasses: ['modal-image-manipulation'],
      buttons: [],
      staticBackdrop: true,
      content: trigger.data('base') + '&modal=1&target=' + encodeURIComponent(trigger.data('target')) + '&target-language=' + encodeURIComponent(trigger.data('target-language')),
      type: Modal.types.iframe,
      size: Modal.sizes.large,
      title: trigger.data('title'),
    });
    this.triggerTarget = $(trigger.data('output-target'));

    if (currentModal.css) {
      // Typo3 11
      currentModal.css('pointer-events', 'none');
    }

  }
}

export default new AlternativeGenerator();
