/**
 * Module: TYPO3/CMS/AiTools/AlternativeGenerator
 *
 * @exports TYPO3/CMS/AiTools/AlternativeGenerator
 */
define([
  'jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/AiTools/RemoteCalls',
  'TYPO3/CMS/Backend/Utility/MessageUtility'
], function ($, Modal, RemoteCalls, MessageUtility) {
  'use strict';

  /**
   * @exports TYPO3/CMS/AiTools/AlternativeGenerator
   */
  var AlternativeGenerator = {};

  AlternativeGenerator.clickSettingsHandler = function (event) {
    event.preventDefault();
    event.stopPropagation();

    AlternativeGenerator.show($(event.currentTarget));
  }

  AlternativeGenerator.initializeTrigger = function () {
    RemoteCalls.initGeneratorButton();
    $('.t3js-alternative-generator-settings-trigger').off('click').on('click', AlternativeGenerator.clickSettingsHandler);
  };

  let currentModal = null;
  let triggerTaget = null;
  AlternativeGenerator.show = function (trigger) {
    currentModal = Modal.advanced({
      additionalCssClasses: ['modal-image-manipulation'],
      buttons: [
      ],
      staticBackdrop: true,
      content: trigger.data('base') + '&modal=1&target=' + encodeURIComponent(trigger.data('target')) + '&target-language=' + encodeURIComponent(trigger.data('target-language')),
      type: Modal.types.iframe,
      size: Modal.sizes.large,
      title: trigger.data('title'),
    });
    triggerTaget = $(trigger.data('output-target'));

    if (currentModal.css) {
      // Typo3 11
      currentModal.css('pointer-events', 'none');
    }
  };

  window.addEventListener('message', function (e) {
    if (!MessageUtility.MessageUtility.verifyOrigin(e.origin)) {
      throw 'Denied message sent by ' + e.origin;
    }

    if (e.data.actionName === 'typo3:aiTools:updateField') {
      if (typeof e.data.value === 'undefined') {
        throw 'value not defined in message';
      }

      if (currentModal) {
        triggerTaget.val(e.data.value);
        if (currentModal.css) {
          // Typo3 11
          currentModal.modal('hide');
        } else {
          // Typo3 12
          currentModal.hideModal();
        }
      }
    }
  });

  AlternativeGenerator.initializeTrigger();

  return AlternativeGenerator;
});
