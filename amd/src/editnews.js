// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    local_shopping_cart
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {showNotification} from 'local_shopping_cart/notifications';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    ADDEDITBUTTON: 'div.wb-news-addeditbutton',
};

export const init = () => {
    // eslint-disable-next-line no-console
    console.log('run init');

    // Cashout functionality.
    const button = document.querySelector(SELECTORS.ADDEDITBUTTON);

    // eslint-disable-next-line no-console
    console.log('button', button);

    if (button) {
        button.addEventListener('click', e => {

            // eslint-disable-next-line no-console
            console.log(e.target);

            addeditformModal(button);
        });
    }
};

/**
 * Show add edit form.
  * @param {htmlElement} button
  * @return [type]
  *
  */
export function addeditformModal(button) {

    // eslint-disable-next-line no-console
    console.log('button', button);

    const id = button.dataset.id;

    const modalForm = new ModalForm({

        // Name of the class where form is defined (must extend \core_form\dynamic_form):
        formClass: "local_wb_news\\form\\action_form",
        // Add as many arguments as you need, they will be passed to the form:
        args: {
            id
        },
        // Pass any configuration settings to the modal dialogue, for example, the title:
        modalConfig: {title: getString('addeditform', 'local_wb_news')},
        // DOM element that should get the focus after the modal dialogue is closed:
        returnFocus: button
    });
    // Listen to events if you want to execute something on form submit.
    // Event detail will contain everything the process() function returned:
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
        const response = e.detail;
        // eslint-disable-next-line no-console
        console.log('cashoutModal response: ', response);

        showNotification('x', 'info');
    });

    // Show the form.
    modalForm.show();

}