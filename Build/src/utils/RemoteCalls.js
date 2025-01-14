import Modal from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';

const URLS = {
  images: TYPO3.settings.ajaxUrls['aitools_ai_tools_images'],
  credits: TYPO3.settings.ajaxUrls['aitools_ai_tools_credits'],
  badwords: TYPO3.settings.ajaxUrls['aitools_ai_tools_badwords'],
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
    };
    xhr.send(paramString);
  });
}

export async function callAjaxMetaGenerateAction(fileIdentifier, targetLanguage, textPrompt, textLabel) {
  const params = {
    action: 'generateMetaData',
    target: fileIdentifier,
    "target-language": targetLanguage,
    textPrompt: textPrompt,
    imageLabel: textLabel
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

export async function triggerBadwordButton(badword, imagelabelid, badwordid, action, funktion) {
  if ((funktion == "badword" || funktion == "metabadword") && (badword === "" || badword == ",")) {
      await showModalConfirmation("You need to enter a badword to proceed.", "Parameters Missing");
      return;
  }

  if (imagelabelid < 0) {
      await showModalConfirmation("You need to select a label to proceed.", "Parameters Missing");
      return;
  }

  let parameters = {};
  let badwords = [];
  if (funktion == "badword" || funktion == "metabadword") {
      badwords = badword.split(',');
      parameters = { funktion: funktion, badword: badwords[0], imagelabelid: imagelabelid, badwordid: badwordid, action: action };
  } else if (funktion == "label") {
      parameters = { funktion: funktion, imagelabelid: imagelabelid, action: action };
  }

  let dup = [];
  if (action == "cut") {
      await showModalConfirmation("Are you sure you want to delete this record?", "Confirmation");
  }

  console.log(parameters);
  await ajaxCall(parameters, URLS.badwords)
      .then(response => {
          console.log('Erfolg:', response);
      })
      .catch(error => {
          if (error === "Error: status 409") {
              dup.push(badwords[0]);
          }
      });

  if ((funktion == "badword" || funktion == "metabadword") && action === "add" && badwords.length > 1) {
      let tmpbadwords = badwords.slice(1);
      for (const word of tmpbadwords) {
          parameters.badword = word;
          await ajaxCall(parameters, URLS.badwords)
              .then(response => {
                  console.log('Erfolg:', response);
              })
              .catch(error => {
                  if (error === "Error: status 409") {
                      dup.push(word);
                  }
              });
      }
  }

  if (dup.length >= 1) {
      await showModalConfirmation("The word" 
          + (dup.length === 1 ? " " : "s ") 
          + dup.join(", ")
          + " could not be " 
          + (action === "add" ? "added" : "changed") 
          + " because " + (dup.length === 1 ? "it" : "they") 
          + " already exists.", "Duplicate");
  }

  if (dup.length > 0 && dup.length === badwords.length) {
      return;
  }

  setTimeout(() => {
      window.location.reload();
  }, 100);
};

async function showModalConfirmation(message, type) {
  return new Promise((resolve) => {
      if (typeof Modal !== 'undefined' && Modal.confirm) {
          Modal.confirm(
              type,
              message,
              Severity.warning,
              [
                  {
                      text: 'Ok',
                      active: true,
                      btnClass: 'btn-warning',
                      name: 'ok',
                      trigger: function () {
                          resolve(true);
                          Modal.dismiss();
                      }
                  }
              ]
          );
      } else {
          resolve(confirm(message));
      }
  });
}
