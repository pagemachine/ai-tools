import $ from 'jquery';
import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import { MessageUtility }  from '@typo3/backend/utility/message-utility';
import GeneratorButton from './utils/GeneratorButton.js';
import { callAjaxSaveMetaDataAction} from './utils/RemoteCalls.js';

$(() => {
  $('.textPromptSelect').on('change', function() {
    const selectedValue = $(this).val();
    $($(this).data('target')).val(selectedValue);
  });
});

$(() => {
  $('.textLabelSelect').on('change', function() {
    const selectedValue = $(this).val();
    $($(this).data('target')).val(selectedValue);
    toggletags(selectedValue, $(this).data("fileid"));
  });
});

let hidden = true;
$(() => {
  $('.hide-div').on('click', function() {
    if(!hidden){
      $("#hide-div-" + $(this).data("file")).hide();
    }
    else{
      $("#hide-div-" + $(this).data("file")).css("display", "block");
    }
    hidden = !hidden;
  });
});

$(() => {
  $('.reset-tag, .global-reset-tag').on('click', function() {
    let file = $(this).data("file");
    let uid = $("#selectedimageLabel-" + file).val()
    toggletags(uid, file);
    if(file == "123"){
      $(".reset-tag").trigger("click");
    }
  });
});

function toggletags(selectedValue, fileid){
  if(selectedValue == -1){
    $("#tag-div-" + fileid).find(".tag , .default-tag").each(function() {
        $(this).hide();
    });
  }
  else{
    $("#tag-div-" + fileid).css("display", "block");
    $("#tag-div-" + fileid).find(".tag , .default-tag").each(function() {
      let val = $(this).data("imagelabelid");
      if (val == 0 || selectedValue == val) {
        $(this).css("display", "inline-block");
      } else {
        $(this).hide();
      }
    });
  }
}

let id = 0;
$(document).on("click", ".add-tag, .global-add-tag", function() {
  let fileKey = $(this).data("file");
  let inputValue = $('#tmp-add-badword-' + fileKey).val();
  console.log(inputValue + " " + fileKey);
  if (!inputValue) return;

  $("#tag-div-" + fileKey).children().eq(1).before(`
    <span 
      class="tmp-tag" 
      id="tag-tmp-${id}-${fileKey}" 
      data-value="${inputValue}" 
      data-imagelabelid="0" 
      data-file="${fileKey}">
      ${inputValue}
      <a href="#" class="remove-tag" data-id="#tag-tmp-${id}-${fileKey}" data-file="${fileKey}">X</a>
    </span>
  `);
  
  id += 1;

  if(fileKey == "123"){
    $(".add-container").each(function() {
      $(this).val(inputValue);
    });
    $(".add-tag").trigger("click");
    $(".add-container").each(function() {
      $(this).val("");
    });
  }

  $('#tmp-add-badword-' + fileKey).val("");
});

$(document).on("click", ".remove-tag", function() {
  let rem = false;
  if ($(this).hasClass("tmp-tag")) {
    rem = true;
  }
  let tmp = $($(this).data("id")).data("value")
  if($(this).data("file") == "123"){
    $(".scroll-container").each(function() {
      $(this).find(".default-tag, .tag, .tmp-tag").each(function() {
          var dataValue = $(this).data("value");
          if (dataValue === tmp) { 
            if(rem){
              $(this).remove();
            }
            else{
              $(this).hide();
            }
          }
      });
  });
  }
  if(rem){
    $($(this).data("id")).remove();
  }
  else{
    $($(this).data("id")).hide();
  }
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
    const selectedValue = $(this).val();
    $($(this).data('target')).val(selectedValue);
    toggletags(selectedValue, $(this).data("fileid"));
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

