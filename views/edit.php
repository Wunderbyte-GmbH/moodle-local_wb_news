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
 *
 * @package   local_wb_news
 * @copyright 2024 Thomas Winkler
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_news;

// @codingStandardsIgnoreStart
require('../../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('../classes/news_form.php');
require_once('../classes/common.php');


$context = \context_system::instance();
$PAGE->set_context($context);

$newsid = optional_param('id', -1, PARAM_INT);

$pageurl = new \moodle_url('/local/news_manager/views/edit.php', array("id" => $newsid));
$manageurl = new \moodle_url('/local/news_manager/views/manage.php');
$PAGE->set_url($manageurl);

require_capability('local/news_manager:manage', $context);

$mform = new news_form(null, array('id' => $newsid));

if ($newsid == -1 || !$DB->record_exists("local_wb_news", array("id" => $newsid), '*')) {
    redirect($manageurl);
}
    $record = $DB->get_record("local_wb_news", array("id" => $newsid), '*');
    $entry = new \stdClass();
    $entry->title_field = $record->title;
    $entry->content_field = array("text" => $record->description, "format" => "1");
    $entry->date_field = $record->date;
    $entry->coverimage = $record->itemid;
    $entry->id = $newsid;

    $mform->set_data($entry);


if ($mform->is_cancelled()) {
    redirect($manageurl, '', 0);
} else if ($data = $mform->get_data()) {
    global $DB;
    $name = $mform->get_new_filename('coverimage');
    $filecontent = $mform->get_file_content('coverimage');

    $rec = $mform->save_stored_file('coverimage',
        \context_system::instance()->id,
        'local_wb_news',
        'coverimage',
        $USER->id,
        '/',
        $name,
        true);

    $file = array(
        'id' => $data->id,
        'contextid' => \context_system::instance()->id,
        'component' => 'local_wb_news',
        'filearea' => 'coverimage',
        'filepath' => '/',
        'itemid' => $data->coverimage,
        'userid' => $USER->id,
        'filename' => $name,
        'title' => $data->title_field,
        'description' => $data->content_field['text'],
        'date' => $data->date_field,
    );

    $DB->update_record('local_wb_news', $file);
    $event = \local_wb_news\event\news_updated::create(array('context' => $PAGE->context,
        'objectid' => $data->id,
        'userid' => $USER->id));
    $event->trigger();
    $mform->set_data($data);
    redirect($manageurl, '', 0);
} else {
    $PAGE->set_title(get_string('pluginname', 'local_wb_news'));
    $PAGE->set_heading(get_string('pluginname', 'local_wb_news'));
    $PAGE->set_pagelayout('standard');

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();

}
