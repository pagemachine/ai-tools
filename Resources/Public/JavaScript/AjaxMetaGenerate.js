require(['jquery', 'TYPO3/CMS/AiTools/RemoteCalls', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function($, RemoteCalls, Modal, Severity) {

  $(() => {
    $('.textPromptSelect').on('change', function() {
      const selectedValue = $(this).val();
      $($(this).data('target')).val(selectedValue);
    });
  });

  $(() => {
    RemoteCalls.initGeneratorButton();
  });

});
