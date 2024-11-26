export async function ajaxCall(parameters, url = ajaxUrl) {
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

export async function callAjaxMetaGenerateAction(fileIdentifier, targetLanguage, textPrompt) {
  return 'yes';
}
