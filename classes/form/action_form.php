<?php
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

/**
 * Entitiesrelation form implemantion to use entities in other plugins
 * @package     local_emis
 * @copyright   2023 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_news\form;
use local_wb_news\news;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

use context;
use core_form\dynamic_form;
use moodle_url;
use context_system;

/**
 * Add file form.
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Thomas Winkler
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_form extends dynamic_form {

    /**
     * {@inheritdoc}
     * @see moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_ajaxformdata;

        $mform = $this->_form; // Don't forget the underscore!

        // ID of the news instance.
        $mform->addElement('hidden', 'instanceid', $customdata['instanceid'] ?? 0);
        $mform->setType('instanceid', PARAM_INT);

        // ID of the news item.
        $mform->addElement('hidden', 'id', $customdata['id'] ?? 0);
        $mform->setType('id', PARAM_INT);

        // Add headline field.
        $mform->addElement('text', 'headline', get_string('headline', 'local_wb_news'));
        $mform->setType('headline', PARAM_TEXT);
        $mform->addRule('headline', null, 'required', null, 'client');

        // Add subheadline field.
        $mform->addElement('text', 'subheadline', get_string('subheadline', 'local_wb_news'));
        $mform->setType('subheadline', PARAM_TEXT);
        $mform->addRule('subheadline', null, 'required', null, 'client');

        // Add description field.
        $mform->addElement('editor', 'description_editor', get_string('description', 'local_wb_news'));
        $mform->setType('description_editor', PARAM_RAW);

        // Add background image field.
        $mform->addElement('text', 'bgimage', get_string('bgimage', 'local_wb_news'));
        $mform->setType('bgimage', PARAM_TEXT);
        $mform->addRule('bgimage', null, 'required', null, 'client');

        // Add icon field.
        $mform->addElement('text', 'icon', get_string('icon', 'local_wb_news'));
        $mform->setType('icon', PARAM_TEXT);

        // Add button link field.
        $mform->addElement('text', 'btnlink', get_string('btnlink', 'local_wb_news'));
        $mform->setType('btnlink', PARAM_TEXT);

        // Add button text field.
        $mform->addElement('text', 'btntext', get_string('btntext', 'local_wb_news'));
        $mform->setType('btntext', PARAM_TEXT);
    }

    /**
     * Check access for dynamic submission.
     *
     * @return void
     */
    protected function check_access_for_dynamic_submission(): void {
        // TODO: capability to create advisors

    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * Submission data can be accessed as: $this->get_data()
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        global $CFG, $DB;

        $data = $this->get_data();

        $data = file_postupdate_standard_editor(
            // The submitted data.
            $data,
            // The field name in the database.
            'description',
            // The options.
            news::get_textfield_options(),
            context_system::instance(),
            'local_wb_news',
            'wb_news',
            $data->id
        );

        $news = news::getinstance($data->instanceid ?? 0);
        $news->update_news($data);

        return $data;
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     *     $this->set_data(get_entity($this->_ajaxformdata['cmid']));
     */
    public function set_data_for_dynamic_submission(): void {
        $ajaxformdata = $this->_ajaxformdata;

        $id = $ajaxformdata['id'] ?? 0;
        $instanceid = $ajaxformdata['instanceid'] ?? 0;
        $news = news::getinstance($instanceid);
        $data = $news->get_news_item($id);

        $context = context_system::instance();
        $data = file_prepare_standard_editor(
                // The existing data.
                $data,
                // The field name in the database.
                'description',
                // The options.
                news::get_textfield_options(),
                // The combination of contextid, component, filearea, and itemid.
                $context,
                'local_wb_news',
                'wb_news',
                $data->id
            );

        $this->set_data($data);
    }

    /**
     * Returns form context
     *
     * If context depends on the form data, it is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        global $USER;
        return context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * If the form has arguments (such as 'id' of the element being edited), the URL should
     * also have respective argument.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/wb_news/index.php');
    }

    /**
     * {@inheritdoc}
     * @see moodleform::validation()
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     */
    public function validation($data, $files) {

        $errors = [];

        return $errors;
    }

    /**
     * {@inheritDoc}
     * @see moodleform::get_data()
     */
    public function get_data() {
        $data = parent::get_data();
        return $data;
    }
}
