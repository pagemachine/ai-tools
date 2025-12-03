import $ from 'jquery';
import {callAjaxMetaGenerateAction} from './RemoteCalls.js';

class GeneratorButton {
  static CSS_CLASSES = {
    TRIGGER: 't3js-alternative-generator-trigger',
    CLICK_HANDLED: 'click-handled',
    GENERATING: 'generating',
    AI_TOOLS_GENERATING: 't3js-ai-tools-generating'
  };

  updateHook = null;

  constructor() {
    this.initGeneratorButton();
  }

  initGeneratorButton() {
    const elements = $(`.${GeneratorButton.CSS_CLASSES.TRIGGER}`).not(`.${GeneratorButton.CSS_CLASSES.CLICK_HANDLED}`);
    elements.addClass(GeneratorButton.CSS_CLASSES.CLICK_HANDLED);
    elements.on('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.triggerGeneratorButton($(e.currentTarget));
    });
  }

  async triggerGeneratorButton(element) {
    const fileIdentifier = element.data('file-identifier');
    const targetLanguage = element.data('target-language');
    const target = $(element.data('output-target'));
    const showTarget = element.data('show-target');

    let textPrompt = element.data('text-prompt');
    if (element.data('text-prompt-field')) {
      textPrompt = $(element.data('text-prompt-field')).val();
    }
    let translationProvider = element.data('translation-provider');

    element.prop('disabled', true);
    element.addClass(GeneratorButton.CSS_CLASSES.GENERATING);

    target.prop('disabled', true);
    target.addClass(GeneratorButton.CSS_CLASSES.AI_TOOLS_GENERATING);

    try {
      const results = await callAjaxMetaGenerateAction(
        fileIdentifier,
        targetLanguage,
        textPrompt,
        translationProvider,
      );

      console.log('Prompt generated', results);
      target.val(results.alternative);
      target.trigger('change');

      if (this.updateHook) {
        this.updateHook(target, results);
      }
    } finally {
      element.prop('disabled', false);
      element.removeClass(GeneratorButton.CSS_CLASSES.GENERATING);

      target.prop('disabled', false);
      target.removeClass(GeneratorButton.CSS_CLASSES.AI_TOOLS_GENERATING);

      if (showTarget) {
        $(showTarget).show();
      }
    }
  }
}

export default GeneratorButton;
