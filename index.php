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

use local_wb_news\output\wb_news;

// @codingStandardsIgnoreStart
require('../../config.php');
// @codingStandardsIgnoreEnd
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');

$context = \context_system::instance();
$PAGE->set_context($context);

$id = optional_param('id', 0, PARAM_INT);

$pageurl = new \moodle_url('/local/wb_news/index.php?id=' . $id);
$PAGE->set_url($pageurl);

$record = $DB->get_record("local_wb_news", ["id" => $id], '*');

$PAGE->set_title($record->title ?? 'title');
$PAGE->set_heading($record->title ?? 'title');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$news = new wb_news($id);
$data = $news->return_list();

// Here, we want the information how to include the instance:
foreach ($data['instances'] as $key => $value) {
    $data['instances'][$key]['instancenameonindex'] = $value["name"];
    $data['instances'][$key]['shortcode'] = "[wbnews instance=" . $value["instanceid"] . "]";
}


$out = $OUTPUT->render_from_template('local_wb_news/wb_news_container', $data);
echo $out;

echo $OUTPUT->footer();

