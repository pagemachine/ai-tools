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
        top.TYPO3.Notification.error('Error', 'Error while generating Metadata (empty response)', 5);
        throw 'Error: empty response';
      }).catch(error => {
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
        top.TYPO3.Notification.error('Error', 'Error while saving Metadata (empty response)', 5);
        throw 'Error: empty response';
      }).catch(error => {
        throw error;
      });
  }

  RemoteCalls.initGeneratorButton = function() {
    const elements = $('.t3js-alternative-generator-trigger').not('.click-handled');
    elements.addClass('click-handled');
    elements.on('click', async function(e) {
      e.preventDefault();
      e.stopPropagation();

      const fileIdentifier = $(this).data('file-identifier');
      const targetLanguage = $(this).data('target-language');
      const target = $($(this).data('output-target'));
      const showTarget = $(this).data('show-target');

      let textPrompt = $(this).data('text-prompt');
      if ($(this).data('text-prompt-field')) {
        textPrompt = $($(this).data('text-prompt-field')).val();
      }

      $(this).prop('disabled', true);
      $(this).addClass('generating');

      target.prop('disabled', true);
      target.addClass('t3js-ai-tools-generating');

      const results = await RemoteCalls.callAjaxMetaGenerateAction(
        fileIdentifier,
        targetLanguage,
        textPrompt
      ).finally(() => {
        $(this).prop('disabled', false);
        $(this).removeClass('generating');

        target.prop('disabled', false);
        target.removeClass('t3js-ai-tools-generating');

        if (showTarget) {
          $(showTarget).show();
        }
      });

      console.log('Prompt generated', results);

      target.val(results.alternative);
      target.trigger('change');
    });
  };

  return RemoteCalls;
});
