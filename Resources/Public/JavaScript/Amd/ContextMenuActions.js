define((function(){"use strict";class e{static getReturnUrl(){return encodeURIComponent(top.list_frame.document.location.pathname+top.list_frame.document.location.search)}static generateAIMetadata(t,o,a){"sys_file"===t&&(console.log("top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images"),console.log(top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images),top.TYPO3.Backend.ContentContainer.setUrl(top.TYPO3.settings.ajaxUrls.aitools_ai_tools_images+"&target-language=0&target="+encodeURIComponent(o)+"&returnUrl="+e.getReturnUrl()))}static folderPermissions(){var t=this.data("folderRecordUid")||0;t>0?top.TYPO3.Backend.ContentContainer.setUrl(top.TYPO3.settings.FormEngine.moduleUrl+"&edit[tx_falsecuredownload_folder]["+parseInt(t,10)+"]=edit&returnUrl="+e.getReturnUrl()):top.TYPO3.Backend.ContentContainer.setUrl(top.TYPO3.settings.FormEngine.moduleUrl+"&edit[tx_falsecuredownload_folder][0]=new&defVals[tx_falsecuredownload_folder][storage]="+this.data("storage")+"&defVals[tx_falsecuredownload_folder][folder]="+this.data("folder")+"&defVals[tx_falsecuredownload_folder][folder_hash]="+this.data("folderHash")+"&returnUrl="+e.getReturnUrl())}}return e}));
