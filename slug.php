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

use local_wb_news\news;
use local_wb_news\output\wb_news;

require_once(__DIR__ . '/../../config.php'); // phpcs:ignore moodle.Files.RequireLogin.Missing

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

$slug = required_param('slug', PARAM_ALPHAEXT);

$returnto = optional_param('returnto', '', PARAM_ALPHA);
$returnurl = optional_param('returnurl', '', PARAM_URL);

$context = context_system::instance();
require_capability('local/wb_news:view', $context);
$PAGE->set_pagetype("local-wb_news-view-id$id");
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/verkehrserziehung/' . $slug);

$id = news::get_id_from_slug($slug);
$newsview = new wb_news($id);

echo $OUTPUT->header();

if (
    $returnto == 'url'
    && !empty($returnurl)
) {
    echo html_writer::link(
        $returnurl,
        '<i class="fa-solid fa-arrow-left lexa-caption smallericon" style="margin-right: 8px;"></i>' . get_string('backtooverview', 'theme_lexa'),
        [
            'role' => 'button',
            'class' => 'btn mr-auto lexa-caption d-flex justify-content-start align-items-center',
            'style' => 'width: fit-content;',
        ]
    );
}
$output = $PAGE->get_renderer('local_wb_news');

echo $output->render_newsinstance($newsview);

echo $OUTPUT->footer();
