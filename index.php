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
require('../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');
require_once('classes/common.php');


$context = \context_system::instance();
$PAGE->set_context($context);

$id = required_param('id', PARAM_INT);

$pageurl = new \moodle_url('/local/news_manager/index.php?id=' . $id);
$PAGE->set_url($pageurl);

$record = $DB->get_record("local_wb_news", array("id" => $id), '*');

$PAGE->set_title($record->title);
$PAGE->set_heading($record->title);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$author = $DB->get_record("user", array("id" => $record->userid), '*');

$coverpic = "/mod/news/img/default.jpg";
if ($record->filename) {
    $coverpic = \moodle_url::make_pluginfile_url($record->contextid, $record->component, $record->filearea,
        $record->userid, $record->filepath, $record->filename, false);
}

$data = [
    'cover' => format_string($coverpic),
    'title' => format_string($record->title),
    'description' => $record->description,
    'date' => gmdate("d.m.y", $record->date),
    'user'  => $author->firstname . ' ' . $author->lastname,
    'coverpic' => $coverpic,
    'userlink' => new \moodle_url('/user/profile.php', array("id" => $author->id)),
];
echo text_to_html($OUTPUT->render_from_template("local_wb_news/detail", $data));

echo $OUTPUT->footer();

