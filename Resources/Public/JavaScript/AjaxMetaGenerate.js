require(['TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function(Modal, Severity) {
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

  // Export the function to global scope
  window.showModalConfirmation = showModalConfirmation;
});

async function callAjaxMetaGenerateActionForAll(button, saveAndTranslate) {
  var imageEntryBlocks = document.querySelectorAll('.imageEntry');
  var progressBar = document.querySelectorAll('.progressBar')[0];
  var skipExistingDescriptions = document.querySelectorAll('.skipExistingDescriptions')[0];

  // show confirmation dialog if skipExistingDescriptions is checked and saveAndTranslate is true
  if (!skipExistingDescriptions.checked && saveAndTranslate) {
    const userConfirmed = await showModalConfirmation('This will overwrite existing descriptions. Are you sure you want to continue?');
    if (!userConfirmed) {
      return;
    }
  }

  progressBar.max = imageEntryBlocks.length;
  progressBar.value = 0;
  button.disabled = true;
  for (let imageEntry of imageEntryBlocks) {
    let textPromptField = imageEntry.querySelector('.textPrompt');
    let genButton = imageEntry.querySelector('.generate-btn');
    let saveAndTranslateButton = imageEntry.querySelector('.save-translate-btn');
    let altTextSuggestion = imageEntry.querySelector('.textarea-altTextSuggestion');
    let fileIdentifierField = imageEntry.querySelector('.fileIdentifierField');

    // skip if altText is already filled and skipExistingDescriptions is checked
    let altText = imageEntry.querySelector('.textarea-altText');
    if (skipExistingDescriptions.checked && altText.value !== '') {
      progressBar.value += 1;
      continue;
    }

    console.log('Progress before:', progressBar.value);

    try {
      if (saveAndTranslate) {
        // generate, save and translate alt-text directly.
        await new Promise((resolve, reject) => {
          genButton.addEventListener('ajaxComplete', resolve, {once: true});
          genButton.addEventListener('ajaxError', reject, {once: true});
          callAjaxMetaGenerateAction(fileIdentifierField.value, altText, textPromptField, genButton, imageEntry)
        });
        await new Promise((resolve, reject) => {
          saveAndTranslateButton.addEventListener('ajaxComplete', resolve, {once: true});
          genButton.addEventListener('ajaxError', reject, {once: true});
          callAjaxMetaSaveAction(fileIdentifierField.value, altText, true, saveAndTranslateButton)
        });
      } else {
        // only generate alt-text and write into suggestion field.
        await new Promise((resolve, reject) => {
          genButton.addEventListener('ajaxComplete', resolve, {once: true});
          genButton.addEventListener('ajaxError', reject, {once: true});
          callAjaxMetaGenerateAction(fileIdentifierField.value, altTextSuggestion, textPromptField, genButton, imageEntry)
        });
      }
    } catch (error) {
      console.error('Error handling AJAX request:', error);
      top.TYPO3.Notification.error('Error', 'Error while generating Metadata', 5);
    }

    progressBar.value += 1;
    console.log('Progress after:', progressBar.value);
  }
  button.disabled = false;
}

function callAjaxMetaGenerateAction(fileIdentifier, textarea, textPromptField, button, blockElement) {
  var oldText = textarea.value;
  var textPrompt = textPromptField.value;
  var originalButtonText = button.textContent;
  button.textContent = 'Generating...';
  button.disabled = true;
  var savedSuccess = false;

  let imageBlockDebugImageRecognizedText = blockElement.querySelector('.debugImageRecognizedText')

  top.TYPO3.Notification.info('Generating Metadata', 'Generating Metadata...', 5);

  var xhr = new XMLHttpRequest();
  var params = 'action=generateMetaData&target=' + encodeURIComponent(fileIdentifier) + '&textPrompt=' + encodeURIComponent(textPrompt);
  xhr.open('POST', ajaxUrl, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
      var response = JSON.parse(this.responseText);
      if (response) {
        textarea.value = response.alternative;
        imageBlockDebugImageRecognizedText.textContent = response.baseAlternative;
        textarea.dispatchEvent(new Event('input'));
        top.TYPO3.Notification.success('Generated Metadata', 'Generated Metadata successful', 5);
        savedSuccess = true;
        button.dispatchEvent(new CustomEvent('ajaxComplete'));
      } else {
        top.TYPO3.Notification.error('Error', 'Error while saving Metadata (empty response)', 5);
        button.dispatchEvent(new CustomEvent('ajaxError'));
      }
    } else if (this.status !== 200) {
      top.TYPO3.Notification.error('Error', 'Error while generating Metadata (status:'+ this.status + ')', 5);
      button.dispatchEvent(new CustomEvent('ajaxError'));
    }
    button.textContent = originalButtonText;
    button.disabled = false;
  }
  xhr.send(params);
}

function callAjaxMetaSaveAction(fileIdentifier, textarea, doTranslate, button) {
  var originalButtonText = button.textContent;
  button.textContent = 'Saving...';
  button.disabled = true;
  let textareaValue = textarea.value

  var xhr = new XMLHttpRequest();
  var params = 'action=saveMetaData&target=' + encodeURIComponent(fileIdentifier) + '&altText=' + encodeURIComponent(textareaValue) + '&translate=' + (doTranslate ? '1' : '0');
  xhr.open('POST', ajaxUrl, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
      var response = JSON.parse(this.responseText);
      if (response) {
        top.TYPO3.Notification.success('Saved Metadata', 'Saved Metadata successful', 5);
        button.dispatchEvent(new CustomEvent('ajaxComplete'));
      } else {
        top.TYPO3.Notification.error('Error', 'Error while saving Metadata (empty response)', 5);
        button.dispatchEvent(new CustomEvent('ajaxError'));
      }
    } else if (this.status !== 200) {
      top.TYPO3.Notification.error('Error', 'Error while saving Metadata (status:'+ this.status + ')', 5);
      button.dispatchEvent(new CustomEvent('ajaxError'));
    }
    button.textContent = originalButtonText;
    button.disabled = false;
  }
  xhr.send(params);
}

function takeSuggestionSaveAction(fileIdentifier, textareaSuggestion, textarea, button) {
  textarea.value = textareaSuggestion.value;
  textarea.dispatchEvent(new Event('input'));
  callAjaxMetaSaveAction(fileIdentifier, textarea, false, button);
}

document.addEventListener('DOMContentLoaded', function() {
  var imageEntryBlocks = document.querySelectorAll('.imageEntry');

  // set save button to disabled if altText is not changed
  imageEntryBlocks.forEach((imageEntry) => {
    let saveBtn = imageEntry.querySelector('.save-btn');
    let altText = imageEntry.querySelector('textarea[name="altText"]');
    altText.addEventListener('input', function() {
      saveBtn.disabled = false;
    });
    saveBtn.disabled = true;

    let textPromptSelect = imageEntry.querySelector('.textPromptSelect');
    let textPrompt = imageEntry.querySelector('.textPrompt');
    textPromptSelect.addEventListener('input', function() {
      textPrompt.value = textPromptSelect.value;
    });

  });

  // set all alTexts fields to globalTextPrompt if globalTextPrompt was changed
  var globalTextPromptField = document.querySelector('.globalTextPrompt');
  // check if there is a globalTextPromptField
  if (globalTextPromptField) {
    globalTextPromptField.addEventListener('input', function () {
      var textPrompts = document.querySelectorAll('.textPrompt');
      textPrompts.forEach((textPrompt) => {
        textPrompt.value = globalTextPromptField.value;
        textPrompt.dispatchEvent(new Event('input'));
      });
      var textPromptSelects = document.querySelectorAll('.textPromptSelect');
      textPromptSelects.forEach((textPromptSelect) => {
        textPromptSelect.value = globalTextPromptField.value;
        textPromptSelect.dispatchEvent(new Event('input'));
      });
    });
  }
});
