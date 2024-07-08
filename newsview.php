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
 * Add dates to option.
 *
 * @package local_wb_news
 * @copyright 2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_wb_news\output\newsview;

require_once(__DIR__ . '/../../config.php'); // phpcs:ignore moodle.Files.RequireLogin.Missing

global $DB, $PAGE, $OUTPUT, $USER;

// We do not want to check login here...
// ...as this page should also be available for not logged in users!

$id = required_param('id', PARAM_INT);
$instanceid = required_param('instanceid', PARAM_INT);

$returnto = optional_param('returnto', '', PARAM_ALPHA);
$returnurl = optional_param('returnurl', '', PARAM_URL);

$context = context_system::instance();
require_capability('local/wb_news:view', $context);

$PAGE->set_context($context);

$url = new moodle_url('/local/wbnews/newsview.php', ['id' => $id, 'instanceid' => $instanceid]);
$PAGE->set_url($url);

$newsview = new newsview($id, $instanceid);

$PAGE->set_title($newsview->return_headline());
$PAGE->set_pagelayout('base');

echo $OUTPUT->header();

if (
    $returnto == 'url'
    && !empty($returnurl)
) {
    echo html_writer::tag(
        'a',
        get_string('back'),
        [
            'class' => 'btn btn-primary',
            'href' => $returnurl,
        ]
    );
}
$output = $PAGE->get_renderer('local_wb_news');
echo $output->render_newsview($newsview);

echo $OUTPUT->footer();
