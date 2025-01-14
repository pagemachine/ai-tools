import $ from 'jquery';
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import { MessageUtility }  from '@typo3/backend/utility/message-utility';
import GeneratorButton from './utils/GeneratorButton.js';
import { callAjaxSaveMetaDataAction, triggerBadwordButton } from './utils/RemoteCalls.js';

$(() => {
  $('.textPromptSelect').on('change', function() {
    const selectedValue = $(this).val();
    $($(this).data('target')).val(selectedValue);
  });
});

$(() => {
  const elements = $('.t3js-alternative-badword-trigger').not('.click-handled');
  elements.addClass('click-handled');

  elements.on('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();

      let badword = $(this).data('text-badword');
      console.log(badword);

      if ($(this).data('text-badword-field')) {
          badword = $($(this).data('text-badword-field')).val().trim();
      }

      console.log("new: " + badword);

      let imagelabelid = $($(this).data('text-imagelabelid-field')).val();
      let badwordid = $(this).data('text-badwordid-field');
      let action = $(this).data('text-action-field');
      let funktion = $(this).data('text-funktion-field');

      const results = await triggerBadwordButton(badword, imagelabelid, badwordid, action, funktion);
  });
});

$(() => {
  $('.textLabelSelect').on('change', function() {
    const selectedValue = $(this).val();
    $($(this).data('target')).val(selectedValue);
  });
});

$(() => {
  $('.badwordtabelbtn').on('click', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const uid = $(this).data('uid-field');
    
    toggletabel(uid, false);
  })
});

$(() => {
  $('.textLabelSelect').on('change', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const uid = $(this).data('fileid');
    toggletabel(uid, true);
  })
});

var lastlabels = [];
function toggletabel(uid, element){
  if(element &&(lastlabels[uid] == undefined || lastlabels[uid] == 0)){
    return
  }
  let thislabel = document.getElementById('selectedimageLabel-' + uid).value;
  let table = document.getElementById('table-' + thislabel + '-' + uid);
  if(lastlabels[uid] != undefined && lastlabels[uid] > 0 && lastlabels[uid] != thislabel){
          console.log(lastlabels[uid]);
          var tmp = document.getElementById('table-' + lastlabels[uid] + '-' + uid);
          tmp.style.display = 'none';
  }
  if(thislabel < 0){
      return;
  }
  if ((table.style.display === 'none')) {
      table.style.display = 'table';
      lastlabels[uid] = thislabel;
  } else {
      table.style.display = 'none';
      lastlabels[uid] = 0;
  }
}

$(() => {
  RemoteCalls.initBadwordButton();
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
    const skipExistingDescriptions = document.getElementById('skipExistingDescriptions').checked;

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

    for (let imageEntry of filteredImageBlocks) {
      try {
        const button = $(imageEntry).find('.t3js-alternative-generator-trigger').first();
        const save = $(imageEntry).find('.t3js-alternative-save-trigger[data-translate="0"]').first();
        const saveTranslate = $(imageEntry).find('.t3js-alternative-save-trigger[data-translate="1"]').first();

        await generator.triggerGeneratorButton(button);

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
  $(".globalTextLabel").on("change", function() {
    $(".textLabelSelect").val($(this).val()).trigger("change");
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
  const skipExistingDescriptions = document.getElementById('skipExistingDescriptions').checked;
  const filteredImageBlocks = getActiveImages(skipExistingDescriptions);
  $('.t3-alternative-generate-all-total-images').text(filteredImageBlocks.length);

  const creditsElements = $(filteredImageBlocks).find('.t3js-ai-tools-credits-view-helper[data-credits]');
  total = 0;
  for (let creditsElement of creditsElements) {
    const credits = Number($(creditsElement).data('credits'));
    if (!isNaN(credits)) {
      total += credits;
    }
  }

  element = document.getElementById('t3-alternative-generate-all-total-credits');
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

