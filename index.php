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

use context_system;
use local_wb_news\output\wb_news;
use stdClass;
use context;

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

if ($id == 0) {
    $record = new stdClass();
    $record->title = get_string('wb_news', 'local_wb_news');
}
$PAGE->set_title($record->title ?? 'title');
$PAGE->set_heading($record->title ?? 'title');
$PAGE->set_pagelayout('base');

echo $OUTPUT->header();

require_capability('local/wb_news:editinstances', context_system::instance());

$news = new wb_news($id);
$data = $news->return_list();

// phpcs:ignore
// Here, we want the information how to include the instance:
foreach ($data['instances'] as $key => $value) {
    if (!empty($data['instances'][$key]['contextids'])) {
        $contextids = explode(',', $data['instances'][$key]['contextids']);
        $hasaccess = false;
        foreach ($contextids as $contextid) {
            if (!empty($contextid)) {
                try {
                    $context = context::instance_by_id($contextid);
                    if (has_capability('local/wb_news:manage', $context)) {
                        $hasaccess = true;
                        $data['instances'][$key]['editmode'] = true;
                    }
                } catch (\Exception $e) {
                    // Do nothing, we will skip this entry.
                    if (is_siteadmin()) {
                        $hasaccess = true;
                    }
                }
            } else {
                $hasaccess = true;
            }
        }

        if (!$hasaccess) {
            unset($data['instances'][$key]);
            continue;
        }
    } else {
        $data['instances'][$key]['editmode'] = true;
    }

    $data['instances'][$key]['instancenameonindex'] = empty($value["name"])
        ? get_string('noname', 'local_wb_news') : $value["name"];
    $data['instances'][$key]['shortcode'] = "[wbnews instance=" . $value["instanceid"] . "]";
    $data['instances'][$key]['isadminpage'] = true;
}

$data['instances'] = array_values($data['instances']);

$data['editmode'] = true;

$out = $OUTPUT->render_from_template('local_wb_news/wb_news_container', $data);
echo $out;

echo $OUTPUT->footer();

