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
    $('.t3js-alternative-save-trigger').on('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();

      const fileIdentifier = $(this).data('file-identifier');
      const targetLanguage = $(this).data('target-language');
      const target = $($(this).data('output-target'));
      const buttons = $($(this).data('button-target'));
      const translate = Number($(this).data('translate'));

      const value = target.val();

      buttons.prop('disabled', true);
      buttons.addClass('saving');
      $(this).addClass('generating');


      const results = await RemoteCalls.callAjaxSaveMetaDataAction(
        fileIdentifier,
        targetLanguage,
        value,
        translate
      ).finally(() => {
        buttons.prop('disabled', false);
        buttons.removeClass('saving');
        $(this).removeClass('generating');
      });

      $('.t3js-alternative-use-trigger').trigger('click');

      console.log('Saving Metadata', results);
    });
  });


  $(() => {
    RemoteCalls.initGeneratorButton();
  });

});
