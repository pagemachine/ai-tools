/**
 * Module: TYPO3/CMS/AiTools/AlternativeGenerator
 *
 * @exports TYPO3/CMS/AiTools/AlternativeGenerator
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal'], function ($, Modal) {
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
    $('.t3js-alternative-generator-settings-trigger').off('click').on('click', AlternativeGenerator.clickSettingsHandler);
  };

  AlternativeGenerator.show = function (trigger) {
    Modal.advanced({
      additionalCssClasses: ['modal-image-manipulation'],
      buttons: [
      ],
      content: trigger.data('base') + '&target=' + encodeURIComponent(trigger.data('target')),
      type: Modal.types.ajax,
      size: Modal.sizes.large,
      title: trigger.data('title'),
    });
  };

  AlternativeGenerator.initializeTrigger();

  return AlternativeGenerator;
});
