import $ from 'jquery';
import Modal from '@typo3/backend/modal';
import { callAjaxMetaGenerateAction } from './utils/RemoteCalls.js';
import MessageUtility from '@typo3/backend/utility/message-utility';

class AlternativeGenerator {
    // ...existing code...
}

console.log('12321312', Modal, callAjaxMetaGenerateAction);
console.log($('title').text());

// Ensure it works both as ES module and AMD
export default AlternativeGenerator;
