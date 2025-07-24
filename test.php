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

use local_wb_news\output\tagview;

require_once(__DIR__ . '/../../config.php'); // phpcs:ignore moodle.Files.RequireLogin.Missing

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

// We do not want to check login here...
// ...as this page should also be available for not logged in users!


$returnto = optional_param('returnto', '', PARAM_ALPHA);
$returnurl = optional_param('returnurl', '', PARAM_URL);

$context = context_system::instance();
require_capability('local/wb_news:view', $context);
$PAGE->set_pagetype("local-wb_news-view-id$id");
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/in-der-schule/' . $slug);
$tagname = 'Radfahren';

// 1. Hole das Tag-Objekt
$tag = core_tag_tag::get_by_name(0, $tagname, 'id');

if (!$tag) {
    echo "Tag nicht gefunden.";
    return;
}

// 2. Hole alle zugehörigen `itemid`-Einträge (das sind die `news.id`-Werte)
$taggeditems = $DB->get_records('tag_instance', array(
    'tagid' => $tag->id,
    'component' => 'local_wb_news',
    'itemtype' => 'local_wb_news'
));

// 3. Extrahiere die IDs
$newsids = array_map(function($item) {
    return $item->itemid;
}, $taggeditems);

// Optional: absichern
$newsids = array_values(array_unique($newsids));
$newsview = new tagview(0);

echo $OUTPUT->header();

$output = $PAGE->get_renderer('local_wb_news');

echo $output->render_newsinstance($newsview);

echo $OUTPUT->footer();
