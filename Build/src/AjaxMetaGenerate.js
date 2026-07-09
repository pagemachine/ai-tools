import $ from 'jquery';
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import { MessageUtility }  from '@typo3/backend/utility/message-utility';
import GeneratorButton from './utils/GeneratorButton.js';
import { callAjaxSaveMetaDataAction, callAjaxBatchGenerateAction } from './utils/RemoteCalls.js';

$(() => {
  $('.textPromptSelect').on('change', function() {
    const selectedValue = JSON.parse($(this).val());
    $($(this).data('target')).val(selectedValue.prompt);
    $($(this).data('target')).attr('data-text-prompt-language', selectedValue.language);
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

    const fileIdentifier = $(this).attr('data-file-identifier');
    const targetLanguage = $(this).data('target-language');
    const translationHash = $(this).data('translation-hash');
    const target = $($(this).data('output-target'));
    const buttons = $($(this).data('button-target'));
    const translate = Number($(this).data('translate'));

    const value = target.val();

    buttons.prop('disabled', true);
    buttons.addClass('saving');
    $(this).addClass('generating');


    const results = await callAjaxSaveMetaDataAction(
      fileIdentifier,
      targetLanguage,
      value,
      translate
    ).finally(() => {
      buttons.prop('disabled', false);
      buttons.removeClass('saving');
      $(this).removeClass('generating');
    });

    setValueInParent(value);

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
  const generator = new GeneratorButton();

  $('.t3js-alternative-generate-all').on('click', async function(e) {
    e.preventDefault();
    e.stopPropagation();

    var progressBar = $('.progressBar').first();

    const translate = Boolean($(this).data('translate'));
    const skipExistingDescriptions = document.getElementById('skipExistingDescriptions')?.checked ?? false;

    if (!skipExistingDescriptions) {
      const userConfirmed = await showModalConfirmation('This will overwrite existing descriptions. Are you sure you want to continue?');
      if (!userConfirmed) {
        return;
      }
    }

    $('.t3js-alternative-generate-all').prop('disabled', true);
    $('.t3js-alternative-generate-all').addClass('generating');

    const filteredImageBlocks = getActiveImages(skipExistingDescriptions);

    progressBar.attr('max', filteredImageBlocks.length);
    progressBar.val(0);
    progressBar.show();

    let aborted = false;
    const $cancelBtn = $('<button class="btn btn-danger btn-pagemachine-ai-tools t3js-alternative-cancel-all" style="margin-left: 10px;">Cancel</button>');
    $cancelBtn.insertAfter($('.t3js-alternative-generate-all').last());
    $cancelBtn.on('click', function(e) {
      e.preventDefault();
      aborted = true;
      $(this).prop('disabled', true).text('Cancelling...');
    });

    const BATCH_SIZE = 10;

    // Collect metadata for all images. Use .attr('data-file-identifier') not .data():
    // jQuery auto-parses a combined identifier like "1:/path/file.jpg" into an object.
    const imageData = filteredImageBlocks.map(imageEntry => {
      const button = $(imageEntry).find('.t3js-alternative-generator-trigger').first();
      const output = $(button.data('output-target'));
      return {
        element: imageEntry,
        fileIdentifier: button.attr('data-file-identifier'),
        targetLanguage: button.data('target-language'),
        textPrompt: button.data('text-prompt-field')
          ? $(button.data('text-prompt-field')).val()
          : button.data('text-prompt'),
        translationProvider: button.data('translation-provider'),
        output: output,
        save: $(imageEntry).find('.t3js-alternative-save-trigger[data-translate="0"]').first(),
        saveTranslate: $(imageEntry).find('.t3js-alternative-save-trigger[data-translate="1"]').first(),
      };
    });

    // Chunk into batches (server caps at 10 images per request).
    const batches = [];
    for (let i = 0; i < imageData.length; i += BATCH_SIZE) {
      batches.push(imageData.slice(i, i + BATCH_SIZE));
    }

    for (const batch of batches) {
      if (aborted) break;

      try {
        const fileIdentifiers = batch.map(d => d.fileIdentifier);
        const results = await callAjaxBatchGenerateAction(
          fileIdentifiers,
          batch[0].targetLanguage,
          batch[0].textPrompt,
          batch[0].translationProvider,
        );

        // Distribute results back to each image field and save.
        for (const item of batch) {
          const result = results[item.fileIdentifier];
          if (result && result.alternative) {
            item.output.val(result.alternative);
            item.output.trigger('change');
            $(item.element).data('alternative', result.alternative);
            $(item.element).css('border', '1px solid green');

            if (translate) {
              if (item.saveTranslate.length) {
                item.saveTranslate.trigger('click');
              }
            } else {
              if (item.save.length) {
                item.save.trigger('click');
              }
            }
          } else {
            $(item.element).css('border', '1px solid red');
          }
        }
      } catch (error) {
        console.error('Batch error', error);
        for (const item of batch) {
          $(item.element).css('border', '1px solid red');
        }
      }

      progressBar.val(progressBar.val() + batch.length);
    }

    $cancelBtn.remove();
    progressBar.hide();
    generateAllListCalculate();

    $('.t3js-alternative-generate-all').prop('disabled', false);
    $('.t3js-alternative-generate-all').removeClass('generating');

  });

  $('.globalTextPrompt').on('change', function() {
    $('.textPromptSelect').val($(this).val()).trigger('change');
  });
});

$(() => {
  document.addEventListener('creditsUpdate', (event) => {
    generateAllListCalculate();
  });

  $('#skipExistingDescriptions').on('change', function() {
    generateAllListCalculate();
  });

  generateAllListCalculate();
});

$(() => {
  $('.t3js-alternative-use-trigger').on('click', async function(e) {
    e.preventDefault();
    e.stopPropagation();

    const target = $($(this).data('output-target'));
    setValueInParent(target.val());
  });
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

function generateAllListCalculate() {
  const skipExistingDescriptions = document.getElementById('skipExistingDescriptions')?.checked ?? false;
  const filteredImageBlocks = getActiveImages(skipExistingDescriptions);
  $('.t3-alternative-generate-all-total-images').text(filteredImageBlocks.length);

  const creditsElements = $(filteredImageBlocks).find('.t3js-ai-tools-credits-view-helper[data-credits]');
  let total = 0;
  for (let creditsElement of creditsElements) {
    const credits = Number($(creditsElement).data('credits'));
    if (!isNaN(credits)) {
      total += credits;
    }
  }

  let element = document.getElementById('t3-alternative-generate-all-total-credits');
  if (element) {
    if (total) {
      element.innerHTML = total + ' Credits';
      element.setAttribute('data-credits', total);
      element.style = '';
    } else {
      element.innerHTML = 'No Credits';
      element.setAttribute('data-credits', 0);
      element.style = 'display: none;';
    }
  }

  const translateBtn = document.querySelector('.t3js-alternative-generate-all[data-translate="1"]');
  const translateCreditElement = document.getElementById('t3-alternative-generate-all-translate-credits');
  if (translateBtn && translateCreditElement) {
    const translationLangCount = parseInt(translateBtn.getAttribute('data-translation-language-count') ?? '0');
    const translateTotal = total + filteredImageBlocks.length * translationLangCount;
    if (translateTotal) {
      translateCreditElement.innerHTML = translateTotal + ' Credits';
      translateCreditElement.style = '';
    } else {
      translateCreditElement.innerHTML = 'No Credits';
      translateCreditElement.style = 'display: none;';
    }
  }
}

function getActiveImages(skipExistingDescriptions) {
  var imageEntryBlocks = document.querySelectorAll('.imageEntry');
  const filteredImageBlocks = [...imageEntryBlocks].filter((imageEntry) => {
    const currentAlternative = $(imageEntry).data('alternative');
    return !skipExistingDescriptions || !currentAlternative
  });
  return filteredImageBlocks;
}

function setValueInParent(value) {
  const message = {
    actionName: 'typo3:aiTools:updateField',
    value: value,
  };
  MessageUtility.send(message, getParent());
}

function getParent() {
  if (
    typeof window.parent !== 'undefined' &&
    typeof window.parent.document.list_frame !== 'undefined' &&
    window.parent.document.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null
  ) {
    return window.parent.document.list_frame;
  } else if (
    typeof window.parent !== 'undefined' &&
    typeof window.parent.frames.list_frame !== 'undefined' &&
    window.parent.frames.list_frame.parent.document.querySelector('.t3js-modal-iframe') !== null
  ) {
    return window.parent.frames.list_frame;
  } else if (
    typeof window.frames !== 'undefined' &&
    typeof window.frames.frameElement !== 'undefined' &&
    window.frames.frameElement !== null &&
    window.frames.frameElement.classList.contains('t3js-modal-iframe')
  ) {
    return (window.frames.frameElement).contentWindow.parent;
  } else if (window.opener) {
    return window.opener;
  }
}

