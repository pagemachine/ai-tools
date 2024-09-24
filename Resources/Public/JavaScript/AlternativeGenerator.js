/**
 * Module: TYPO3/CMS/AiTools/AlternativeGenerator
 *
 * @exports TYPO3/CMS/AiTools/AlternativeGenerator
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/AiTools/RemoteCalls'], function ($, Modal, RemoteCalls) {
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

  AlternativeGenerator.show = function (trigger) {
    const currentModal = Modal.advanced({
      additionalCssClasses: ['modal-image-manipulation'],
      buttons: [
      ],
      staticBackdrop: true,
      content: trigger.data('base') + '&target=' + encodeURIComponent(trigger.data('target')) + '&target-language=' + encodeURIComponent(trigger.data('target-language')),
      type: Modal.types.ajax,
      size: Modal.sizes.large,
      title: trigger.data('title'),
    });

    currentModal.css('pointer-events', 'none');

    currentModal.on('modal-loaded', function () {
      const use = currentModal.find('.t3js-alternative-use-trigger');
      const save = currentModal.find('.t3js-alternative-save-trigger');
      const target = $(trigger.data('output-target'));

      use.show();
      save.hide();

      use.off('click').on('click', function (e) {
        const value = currentModal.find('.textarea-altTextSuggestion').first().val();
        target.val(value);
        target.trigger('change');
        currentModal.modal('hide');
      });
    });
  };

  AlternativeGenerator.initializeTrigger();

  return AlternativeGenerator;
});
