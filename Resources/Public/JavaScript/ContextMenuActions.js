/**
 * Module: TYPO3/CMS/AiTools/ContextMenuActions
 *
 * @exports TYPO3/CMS/AiTools/ContextMenuActions
 */
define(function () {
  'use strict';

  /**
   * @exports TYPO3/CMS/AiTools/ContextMenuActions
   */
  var ContextMenuActions = {};

  /**
   * Generate AI Metadata for the given file.
   *
   * @param {string} table
   * @param {int} uid of the page
   */
  ContextMenuActions.generateAIMetadata = function (table, uid) {
    //if (table === 'sys_file_metadata') {
      //If needed, you can access other 'data' attributes here from $(this).data('someKey')
      //see item provider getAdditionalAttributes method to see how to pass custom data attributes

      const returnUrl = encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search)

      const t = (0, o.default)(this).data("metadata-uid");
      t && top.TYPO3.Backend.ContentContainer.setUrl(top.TYPO3.settings.FormEngine.moduleUrl + "&edit[sys_file_metadata][" + parseInt(t, 10) + "]=edit&returnUrl=" + returnUrl)

      top.TYPO3.Notification.info('Generated Metadata', 'Generated Metadata via A.I.', 5);
    //}
  };

  return ContextMenuActions;
});
