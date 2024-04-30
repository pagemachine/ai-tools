async function callAjaxMetaGenerateActionForAll(button, saveAndTranslate) {
  var generateBtns = document.querySelectorAll('.generate-btn');
  var progressBar = document.querySelectorAll('.progressBar')[0];
  progressBar.max = generateBtns.length;
  progressBar.value = 0;
  button.disabled = true;
  for (let button of generateBtns) {
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
      let saveAndTranslateButton = button.parentElement.querySelector('.save-translate-btn');
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

document.addEventListener('DOMContentLoaded', function() {
  var altTexts = document.querySelectorAll('textarea[name="altText"]');
  var saveBtns = document.querySelectorAll('.save-btn');

  altTexts.forEach((altText, index) => {
    var saveBtn = saveBtns[index];
    altText.addEventListener('input', function() {
      saveBtn.disabled = false;
    });
    saveBtn.disabled = true;
  });
});
