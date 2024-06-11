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
require_once(__DIR__ . '/../../config.php');

require_admin();
$context = \context_system::instance();
$PAGE->set_context($context);

$pageurl = new \moodle_url('/local/wb_news/demo.php');
$PAGE->set_url($pageurl);

$PAGE->set_title('WB News Demo Page');
$PAGE->set_heading('WB News Demo Page');
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

$coverpic = "/local/wb_news/img/default.jpg";

$newsdata = [
    'news' => [
        [
            'bgimage' => '/local/wb_news/img/wb.jpg',
            'icon' => '/local/wb_news/img/icon.png',
            'headline' => 'News 1',
            'subheadline' => 'Image and Icon',
            'description' => 'Image and Icon',
            'btnlink' => 'https://link1.com',
            'btntext' => 'Read More'
        ],
        [
            'headline' => 'News 2',
            'subheadline' => 'News 2',
            'description' => 'No Image No Icon',
            'btnlink' => 'https://link1.com',
            'btntext' => 'Read More'
        ],
        [
            'icon' => '/local/wb_news/img/icon.png',
            'headline' => 'News 3',
            'subheadline' => 'Icon no Image',
            'description' => 'Icon no Image',
            'btnlink' => 'https://link1.com',
            'btntext' => 'Read More'
        ],
        [
            'bgimage' => '/local/wb_news/img/wb.jpg',
            'headline' => 'News 4',
            'subheadline' => 'Image no Icon',
            'description' => 'Image no Icon',
            'btnlink' => 'https://link1.com',
            'btntext' => 'Read More'
        ],
        [
            'bgimage' => '/local/wb_news/img/wb.jpg',
            'icon' => '/local/wb_news/img/icon.png',
            'headline' => 'News 5',
            'subheadline' => 'Image, Icon, Link, No button',
            'description' => 'Image, Icon, Link, No button',
            'btnlink' => 'https://link1.com',
        ],
        [
            'headline' => 'News 6',
            'subheadline' => 'News 6',
            'description' => 'No Image, No Icon, No link',
        ],
    ]
];

echo "<h2>Bg Image</h2>";
echo $OUTPUT->render_from_template("local_wb_news/wb_news_bgimage", $newsdata);

echo "<h2>Header Image</h2>";
echo $OUTPUT->render_from_template("local_wb_news/wb_news_headerimage", $newsdata);

echo $OUTPUT->footer();

