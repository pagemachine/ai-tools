class ContextMenuActions {

  static getReturnUrl() {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search)
  }

  static generateAIMetadata(table, uid, dataset) {
    if (table === 'sys_file') {
      //If needed, you can access other 'data' attributes here from $(this).data('someKey')
      //see item provider getAdditionalAttributes method to see how to pass custom data attributes
      console.log("top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images");
      console.log(top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images);
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images + '&target-language=0&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
      );

    }
  }

  static folderPermissions() {
    var folderRecordUid = this.data('folderRecordUid') || 0;

    if (folderRecordUid > 0) {
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.FormEngine.moduleUrl
        + '&edit[tx_falsecuredownload_folder][' + parseInt(folderRecordUid, 10) + ']=edit'
        + '&returnUrl=' + ContextMenuActions.getReturnUrl()
      );
    } else {
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.FormEngine.moduleUrl
        + '&edit[tx_falsecuredownload_folder][0]=new'
        + '&defVals[tx_falsecuredownload_folder][storage]=' + this.data('storage')
        + '&defVals[tx_falsecuredownload_folder][folder]=' + this.data('folder')
        + '&defVals[tx_falsecuredownload_folder][folder_hash]=' + this.data('folderHash')
        + '&returnUrl=' + ContextMenuActions.getReturnUrl()
      );
    }
  }
}

export default ContextMenuActions;
