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
require_once('../classes/common.php');

$context = \context_system::instance();
$PAGE->set_context($context);

$pageurl = new \moodle_url('/local/news_manager/views/manage.php');
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_wb_news'));
$PAGE->set_heading(get_string('pluginname', 'local_wb_news'));
$PAGE->set_pagelayout('standard');
require_capability('local/news_manager:manage', $context);

echo $OUTPUT->header();
$tableheaders = array(
    get_string('form_title', 'local_wb_news'),
    get_string('form_content', 'local_wb_news'),
    get_string('form_date', 'local_wb_news'),
    get_string('manage', 'local_wb_news'),
    get_string('delete', 'local_wb_news'));

$pendingtable = new \html_table();
$pendingtable->attributes['class'] = 'table table-striped';
$pendingtable->head = $tableheaders;
$pendingtable->data = array();

$entries = get_news_query();

foreach ($entries as $entry) {
    $pendingtable->data[] = array(
        \html_writer::tag("a", format_string($entry->title),
            array("href" => new \moodle_url('/local/news_manager/index.php', array("id" => $entry->id)))),
        format_string(substr($entry->description, 0, 120) . "..."),
        format_string(gmdate("d. F Y", $entry->date)),
        \html_writer::tag("a", \html_writer::tag("i", "",
            array("class" => "fa fa-pencil-square-o", "aria-hidden" => "true")), array("href" => "edit.php?id=". $entry->id)),
        \html_writer::tag("a", \html_writer::tag("i", "",
            array("class" => "fa fa-trash", "aria-hidden" => "true")),
            array("class" => "trashbin", "itemid" => $entry->id, "href" => "#")),
        );
    $coverpic = \moodle_url::make_pluginfile_url($entry->contextid, $entry->component, $entry->filearea,
    $entry->userid, $entry->filepath, $entry->filename, false);
}
echo \html_writer::table($pendingtable);
echo \html_writer::start_tag("form", array("action" => "create.php"));
echo \html_writer::tag("input", "",
    array("type" => "submit", "class" => "btn btn-primary", "value" => get_string('manage_new', 'local_wb_news')));
echo \html_writer::end_tag("form");

$PAGE->requires->js_call_amd('local_wb_news/newsmanagerlib', 'init');

echo $OUTPUT->footer();
