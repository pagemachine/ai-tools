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


  ContextMenuActions.getReturnUrl = function () {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  };


  /**
   * Generate AI Metadata for the given file.
   * @TODO Update for TYPO3 v12 (https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Deprecation-98168-BindingContextMenuItemToThis.html)
   *
   * @param {string} table
   * @param {int} uid of the page
   */
  ContextMenuActions.generateAIMetadata = function (table, uid) {
    if (table === 'sys_file') {
      //If needed, you can access other 'data' attributes here from $(this).data('someKey')
      //see item provider getAdditionalAttributes method to see how to pass custom data attributes

      //top.TYPO3.Notification.info('Generated Metadata', 'Generated Metadata via A.I.', 5);
      //console.log(top.TYPO3.settings);
      //top.TYPO3.Notification.info('Generated Metadata', top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(), 5);

      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
      );

    }
  };

  return ContextMenuActions;
});
