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

$id = required_param('id', PARAM_INT);

$pageurl = new \moodle_url('/local/news_manager/views/delete.php', array("id" => $id));
$PAGE->set_url($pageurl);

$PAGE->set_title(get_string('pluginname', 'local_wb_news'));
$PAGE->set_heading(get_string('pluginname', 'local_wb_news'));
$PAGE->set_pagelayout('standard');
require_capability('local/news_manager:manage', $context);

if ($DB->record_exists("local_wb_news", array("id" => $id), '*')) {
    $DB->delete_records('local_wb_news', array("id" => $id));
} else {
    redirect("manage.php", '', 0);
}

echo $OUTPUT->notification(
    "Success",
    'notifymessage');

$event = \local_wb_news\event\news_deleted::create(array('context' => $PAGE->context,
    'objectid' => $id,
    'userid' => $USER->id));
$event->trigger();

redirect("manage.php", '', 0);
