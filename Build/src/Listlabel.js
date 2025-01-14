import $ from 'jquery';
import { triggerBadwordButton } from './utils/RemoteCalls.js';

$(() => {
    const elements = $('.t3js-alternative-badword-trigger').not('.click-handled');
    elements.addClass('click-handled');

    elements.on('click', async function(e) {
        e.preventDefault();
        e.stopPropagation();

        let badword = $(this).data('text-badword');
        console.log(badword);

        if ($(this).data('text-badword-field')) {
            badword = $($(this).data('text-badword-field')).val().trim();
        }

        console.log("new: " + badword);

        let imagelabelid = $($(this).data('text-imagelabelid-field')).val();
        let badwordid = $(this).data('text-badwordid-field');
        let action = $(this).data('text-action-field');
        let funktion = $(this).data('text-funktion-field');

        const results = await triggerBadwordButton(badword, imagelabelid, badwordid, action, funktion);
    });
});