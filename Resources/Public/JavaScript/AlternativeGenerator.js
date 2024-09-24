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
    Modal.advanced({
      additionalCssClasses: ['modal-image-manipulation'],
      buttons: [
      ],
      content: trigger.data('base') + '&target=' + encodeURIComponent(trigger.data('target')) + '&target-language=' + encodeURIComponent(trigger.data('target-language')),
      type: Modal.types.ajax,
      size: Modal.sizes.large,
      title: trigger.data('title'),
    });
  };

  AlternativeGenerator.initializeTrigger();

  return AlternativeGenerator;
});
