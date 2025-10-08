const URLS = {
  images: TYPO3.settings.ajaxUrls['aitools_ai_tools_images'],
  credits: TYPO3.settings.ajaxUrls['aitools_ai_tools_credits'],
};

export async function ajaxCall(parameters, url) {
  var paramString = Object.keys(parameters).map(key => key + '=' + encodeURIComponent(parameters[key])).join('&');

  return new Promise((resolve, reject) => {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
        var response = JSON.parse(this.responseText);
        if (response.error) {
          reject(response.error);
          return;
        }
        resolve(response);
      } else if (this.status !== 200) {
        reject('Error: status ' + this.status);
      }
    }
    xhr.send(paramString);
  });
}

export async function callAjaxMetaGenerateAction(fileIdentifier, targetLanguage, textPrompt, textPromptLanguage) {
  const params = {
    action: 'generateMetaData',
    target: fileIdentifier,
    "target-language": targetLanguage,
    textPrompt: textPrompt,
    textPromptLanguage: textPromptLanguage,
  };

  top.TYPO3.Notification.info('Generating Metadata', 'Generating Metadata...', 5);
  return ajaxCall(params, URLS.images)
    .then(response => {
      if (response) {
        top.TYPO3.Notification.success('Generated Metadata', 'Generated Metadata successful', 5);
        return response;
      }
      throw 'Error: empty response';
    }).catch(error => {
      top.TYPO3.Notification.error('Error', '(Meta) Error: ' + error, 5);
      throw error;
    });
}

export async function callAjaxCreditsAction(data) {
  const params = {
    action: 'credits',
    ...data
  };

  return ajaxCall(params, URLS.credits)
    .then(response => {
      if (response) {
        return response;
      }
      throw 'Error: empty response';
    });
}

export async function callAjaxSaveMetaDataAction(fileIdentifier, targetLanguage, altText, translate = 0) {
  const params = {
    action: 'saveMetaData',
    target: fileIdentifier,
    "target-language": targetLanguage,
    altText: altText,
    translate: translate
  };

  return ajaxCall(params, URLS.images)
    .then(response => {
      if (response) {
        top.TYPO3.Notification.success('Saved Metadata successful', 'Saved Metadata successful', 5);
        return response;
      }
      throw 'Error: empty response';
    }).catch(error => {
      top.TYPO3.Notification.error('Error', '(Saving) Error: ' + error, 5);
      throw error;
    });
}
