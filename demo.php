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

// Testdata.
$newsdata = [
    'id' => 'news',
    'news' => [
        [
            'id' => 'news1',
            'bgimage' => '/local/wb_news/img/wb.jpg',
            'icon' => '/local/wb_news/img/icon.png',
            'headline' => 'News 1',
            'subheadline' => 'Headerimage and Icon',
            'description' => 'Image and Icon',
            'btnlink' => 'https://www.wunderbyte.at',
            'btntext' => 'Read More',
            'headerimage' => true,
            'firstitem' => true,
        ],
        [
            'id' => 'news2',
            'headline' => 'News 2',
            'subheadline' => 'News 2',
            'description' => 'No Image No Icon',
            'btnlink' => 'https://www.wunderbyte.at',
            'btntext' => 'Read More',
        ],
        [
            'id' => 'news3',
            'icon' => '/local/wb_news/img/icon.png',
            'headline' => 'News 3',
            'subheadline' => 'Icon no Image',
            'description' => 'Icon no Image',
            'btnlink' => 'https://www.wunderbyte.at',
            'btntext' => 'Read More',
        ],
        [
            'id' => 'news4',
            'bgimage' => '/local/wb_news/img/wb.jpg',
            'headline' => 'News 4',
            'subheadline' => 'Image no Icon',
            'description' => 'Image no Icon',
            'btnlink' => 'https://www.wunderbyte.at',
            'btntext' => 'Read More',
        ],
        [
            'id' => 'news5',
            'bgimage' => '/local/wb_news/img/wb.jpg',
            'icon' => '/local/wb_news/img/icon.png',
            'headline' => 'News 5',
            'subheadline' => 'BGImage, Icon, Link, No button',
            'description' => 'BGImage, Icon, Link, No button',
            'btnlink' => 'https://www.wunderbyte.at',
            'headerimage' => false,

        ],
        [
            'id' => 'news6',
            'headline' => 'News 6',
            'subheadline' => 'News 6',
            'description' => 'No Image, No Icon, No link',
        ],
    ]
];

echo "<h2>Grid</h2>";
echo $OUTPUT->render_from_template("local_wb_news/wb_news_grid", $newsdata);

$masonry['news'] = array_merge($newsdata['news'], $newsdata['news']);
echo "<br><h2>Masonry (with 12 News)</h2>";
echo $OUTPUT->render_from_template("local_wb_news/wb_news_masonry", $masonry);

$groupednews = grouparray($newsdata['news'], 4);

function grouparray($array, $groupsize) {
    $grouped = array();
    for ($i = 0; $i < count($array); $i += $groupsize) {
        $grouped[] = ['news' => array_slice($array, $i, $groupsize)];
    }
    $grouped[0]['firstitem'] = true;
    $grouped[0]['title'] = "tab1";
    $grouped[0]['id'] = "tab1";

    $grouped[1]['title'] = "tab2";
    $grouped[1]['id'] = "tab2";

    return $grouped;
}

echo "<br><h2>Slider</h2>";
echo $OUTPUT->render_from_template("local_wb_news/wb_news_slider", ['groupednews' => $groupednews]);

echo "<br><h2>Tabs</h2>";
echo $OUTPUT->render_from_template("local_wb_news/wb_news_tabs", ['tabs' => $groupednews]);

echo $OUTPUT->footer();

