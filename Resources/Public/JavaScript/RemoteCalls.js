/**
 * Module: TYPO3/CMS/AiTools/RemoteCalls
 *
 * @exports TYPO3/CMS/AiTools/RemoteCalls
 */
define(['jquery'], function ($) {
  'use strict';

  /**
   * @exports TYPO3/CMS/AiTools/RemoteCalls
   */
  var RemoteCalls = {};

  RemoteCalls.ajaxCall = async function(parameters) {
    var paramString = Object.keys(parameters).map(key => key + '=' + encodeURIComponent(parameters[key])).join('&');

    return new Promise((resolve, reject) => {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxUrl, true);
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

  RemoteCalls.callAjaxMetaGenerateAction = async function(fileIdentifier, targetLanguage, textPrompt) {
    const params = {
      action: 'generateMetaData',
      target: fileIdentifier,
      "target-language": targetLanguage,
      textPrompt: textPrompt
    };

    top.TYPO3.Notification.info('Generating Metadata', 'Generating Metadata...', 5);
    return RemoteCalls.ajaxCall(params)
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

  RemoteCalls.callAjaxSaveMetaDataAction = async function(fileIdentifier, targetLanguage, altText, translate = 0) {
    const params = {
      action: 'saveMetaData',
      target: fileIdentifier,
      "target-language": targetLanguage,
      altText: altText,
      translate: translate
    };

    return RemoteCalls.ajaxCall(params)
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

  RemoteCalls.initGeneratorButton = function() {
    const elements = $('.t3js-alternative-generator-trigger').not('.click-handled');
    elements.addClass('click-handled');
    elements.on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      RemoteCalls.triggerGeneratorButton($(this));
    });
  };

  RemoteCalls.triggerGeneratorButton = async function(element) {
    const fileIdentifier = element.data('file-identifier');
    const targetLanguage = element.data('target-language');
    const target = $(element.data('output-target'));
    const showTarget = element.data('show-target');

    let textPrompt = element.data('text-prompt');
    if (element.data('text-prompt-field')) {
      textPrompt = $(element.data('text-prompt-field')).val();
    }

    element.prop('disabled', true);
    element.addClass('generating');

    target.prop('disabled', true);
    target.addClass('t3js-ai-tools-generating');

    const results = await RemoteCalls.callAjaxMetaGenerateAction(
      fileIdentifier,
      targetLanguage,
      textPrompt
    ).finally(() => {
      element.prop('disabled', false);
      element.removeClass('generating');

      target.prop('disabled', false);
      target.removeClass('t3js-ai-tools-generating');

      if (showTarget) {
        $(showTarget).show();
      }
    });

    console.log('Prompt generated', results);

    target.val(results.alternative);
    target.trigger('change');
  };

  return RemoteCalls;
});
