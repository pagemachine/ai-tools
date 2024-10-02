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
      const translationHash = $(this).data('translation-hash');
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

      if (translationHash) {
        for (const translation of results.translations) {
          const textTarget = $('#translate-' + translationHash + '-' + translation.languageId);
          textTarget.text(translation.altTextTranslated);
        }
      }
    });
  });

  $(() => {
    $('.t3js-alternative-generate-all').on('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();

      var imageEntryBlocks = document.querySelectorAll('.imageEntry');
      var progressBar = $('.progressBar').first();

      const translate = Boolean($(this).data('translate'));
      const skipExistingDescriptions = document.getElementById('skipExistingDescriptions').checked;

      if (!skipExistingDescriptions) {
        const userConfirmed = await showModalConfirmation('This will overwrite existing descriptions. Are you sure you want to continue?');
        if (!userConfirmed) {
          return;
        }
      }

      $('.t3js-alternative-generate-all').prop('disabled', true);
      $('.t3js-alternative-generate-all').addClass('generating');

      const filteredImageBlocks = [...imageEntryBlocks].filter((imageEntry) => {
        const currentAlternative = $(imageEntry).data('alternative');
        return !skipExistingDescriptions || !currentAlternative
      });

      progressBar.attr('max', filteredImageBlocks.length);
      progressBar.val(0);

      for (let imageEntry of filteredImageBlocks) {
        try {
          const button = $(imageEntry).find('.t3js-alternative-generator-trigger').first();
          const save = $(imageEntry).find('.t3js-alternative-save-trigger[data-translate="0"]').first();
          const saveTranslate = $(imageEntry).find('.t3js-alternative-save-trigger[data-translate="1"]').first();

          await RemoteCalls.triggerGeneratorButton(button);

          if (translate) {
            if (!saveTranslate.length) {
              console.error('No saveTranslate button found');
              return;
            }
            saveTranslate.trigger('click');
          } else {
            if (!save.length) {
              console.error('No save button found');
              return;
            }
            save.trigger('click');
          }

          $(imageEntry).css('border', '1px solid green');

        } catch (error) {
          console.error('Error while generating metadata', error);
          $(imageEntry).css('border', '1px solid red');
        }

        progressBar.val(progressBar.val() + 1);
      }

      $('.t3js-alternative-generate-all').prop('disabled', false);
      $('.t3js-alternative-generate-all').removeClass('generating');

    });

    $('.globalTextPrompt').on('change', function() {
      $('.textPromptSelect').val($(this).val()).trigger('change');
    });
  });


  $(() => {
    RemoteCalls.initGeneratorButton();
  });

  async function showModalConfirmation(message) {
    return new Promise((resolve) => {
      if (typeof Modal !== 'undefined' && Modal.confirm) {
        Modal.confirm(
          'Confirmation Required',
          message,
          Severity.warning,
          [
            {
              text: 'Yes',
              active: true,
              btnClass: 'btn-warning',
              name: 'yes',
              trigger: function () {
                resolve(true);
                Modal.dismiss();
              }
            },
            {
              text: 'No',
              name: 'no',
              trigger: function () {
                resolve(false);
                Modal.dismiss();
              }
            }
          ]
        );
      } else {
        // Fallback if Modal.confirm is not available
        resolve(confirm(message));
      }
    });
  }

});
