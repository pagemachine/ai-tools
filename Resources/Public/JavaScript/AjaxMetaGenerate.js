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
    let button = imageEntry.querySelector('.generate-btn');

    // skip if altText is already filled and skipExistingDescriptions is checked
    let altText = imageEntry.querySelector('.altText');
    if (skipExistingDescriptions.checked && altText.value !== '') {
      progressBar.value += 1;
      continue;
    }

    let buttonInitiallyDisabled = button.disabled; // Check initial state
    console.log('Progress before:', progressBar.value);
    await new Promise((resolve, reject) => {
      let hasBeenDisabled = false; // Flag to track if the button has been disabled

      const observer = new MutationObserver((mutationsList, observer) => {
        for (let mutation of mutationsList) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
            if (button.disabled) {
              hasBeenDisabled = true; // Mark as disabled
            } else if (!button.disabled && hasBeenDisabled) {
              // Only resolve if the button was disabled and then re-enabled
              observer.disconnect();
              resolve();
            }
          }
        }
      });

      observer.observe(button, { attributes: true });
      if (!buttonInitiallyDisabled) {
        button.click(); // Click the button if it wasn't already disabled
      }
    });

    if (saveAndTranslate) {
      //let saveAndTranslateButton = button.parentElement.querySelector('.save-translate-btn');
      let saveAndTranslateButton = imageEntry.querySelector('.save-translate-btn');

      // set altText to altTextSuggestion
      let altText = imageEntry.querySelector('.textarea-altText');
      let altTextSuggestion = imageEntry.querySelector('.textarea-altTextSuggestion');
      altText.value = altTextSuggestion.value;
      altText.dispatchEvent(new Event('input'));

      await new Promise((resolve, reject) => {
        let hasBeenDisabled = false; // Reset flag for the next button

        const observer = new MutationObserver((mutationsList, observer) => {
          for (let mutation of mutationsList) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
              if (saveAndTranslateButton.disabled) {
                hasBeenDisabled = true; // Mark as disabled
              } else if (!saveAndTranslateButton.disabled && hasBeenDisabled) {
                // Only resolve if the button was disabled and then re-enabled
                observer.disconnect();
                resolve();
              }
            }
          }
        });

        observer.observe(saveAndTranslateButton, { attributes: true });
        saveAndTranslateButton.click(); // Click after setting up the observer
      });
    }
    progressBar.value += 1;
    console.log('Progress after:', progressBar.value);
  }
  button.disabled = false;
}

function callAjaxMetaGenerateAction(fileIdentifier, textarea, textPromptField, languageSelectField, button) {
  var oldText = textarea.value;
  var textPrompt = textPromptField.value;
  var originalButtonText = button.textContent;
  button.textContent = 'Generating...';
  button.disabled = true;
  var savedSuccess = false;

  top.TYPO3.Notification.info('Generating Metadata', 'Generating Metadata...', 5);

  var xhr = new XMLHttpRequest();
  var params = 'action=generateMetaData&target=' + encodeURIComponent(fileIdentifier) + '&textPrompt=' + encodeURIComponent(textPrompt);
  xhr.open('POST', ajaxUrl, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
      var response = JSON.parse(this.responseText);
      if (response.alternative !== '' && response.alternative !== oldText) {
        textarea.value = response.alternative;
        textarea.dispatchEvent(new Event('input'));
        top.TYPO3.Notification.success('Generated Metadata', 'Generated Metadata successful', 5);
        savedSuccess = true;
      }
    } else if (this.status !== 200) {
      top.TYPO3.Notification.error('Error', 'Error while generating Metadata', 5);
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

  var xhr = new XMLHttpRequest();
  var params = 'action=saveMetaData&target=' + encodeURIComponent(fileIdentifier) + '&altText=' + encodeURIComponent(textarea.value) + '&translate=' + (doTranslate ? '1' : '0');
  xhr.open('POST', ajaxUrl, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
      var response = JSON.parse(this.responseText);
      if (response) {
        top.TYPO3.Notification.success('Saved Metadata', 'Saved Metadata successful', 5);
      } else {
        top.TYPO3.Notification.error('Error', 'Error while saving Metadata', 5);
      }
    } else if (this.status !== 200) {
      top.TYPO3.Notification.error('Error', 'Error while saving Metadata', 5);
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
  });

  // set all alTexts fields to globalTextPrompt if globalTextPrompt was changed
  var globalTextPromptField = document.querySelector('.globalTextPrompt');
  globalTextPromptField.addEventListener('input', function() {
    var textPrompts = document.querySelectorAll('.textPrompt');
    textPrompts.forEach((textPrompt) => {
      textPrompt.value = globalTextPromptField.value;
      textPrompt.dispatchEvent(new Event('input'));
    });
  });
});
