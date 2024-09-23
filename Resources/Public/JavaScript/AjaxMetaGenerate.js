require(['jquery', 'TYPO3/CMS/AiTools/RemoteCalls', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function($, RemoteCalls, Modal, Severity) {

  $(() => {
    $('.textPromptSelect').on('change', function() {
      const selectedValue = $(this).val();
      $($(this).data('target')).val(selectedValue);
    });
  });

  $(() => {
    $('.t3js-alternative-use-current-trigger').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const target = $($(this).data('output-target'));
      const text = $(this).data('current-text');
      const showTarget = $(this).data('show-target');

      target.val(text);
      if (showTarget) {
        $(showTarget).show();
      }
    });
  });


  $(() => {
    RemoteCalls.initGeneratorButton();
  });

});
