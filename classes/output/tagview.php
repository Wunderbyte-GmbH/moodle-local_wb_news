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
 * Contains class mod_questionnaire\output\indexpage
 *
 * @package    local_wb_news
 * @copyright  2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace local_wb_news\output;

use core_tag_tag;
use local_wb_news\news;
use renderable;
use renderer_base;
use templatable;
use context_system;

/**
 * viewtable class to display view.php
 * @package local_wb_news
 *
 */
class tagview implements renderable, templatable {

    /**
     * News items is the array used for output.
     *
     * @var array
     */
    private $news = [];

    /**
     * Instanceid, 0 for all items
     *
     * @var int
     */
    private $instanceid = 0;

    /**
     * Template
     *
     * @var string
     */
    private string $template = '';

    /**
     * Columns
     *
     * @var int
     */
    private int $columns = 4;

    /**
     * Constructor.
     *
     * @param int $instanceid = 0;
     */
    public function __construct($inarray) {

        $instanceid = 0;


        global $PAGE;

        $this->instanceid = $instanceid;

        $news = news::getinstance($instanceid);

        foreach ($news->return_list_of_news() as $newsitem) {
            $newsitem['editmode'] = $PAGE->user_is_editing();
            $this->news[] = $newsitem;
        }
        $this->template = $news->return_template();
        $this->columns = $news->return_columns();
    }

    /**
     * Returns the items of this class.
     *
     * @return array
     *
     */
    public function return_list() {
        global $PAGE, $DB;
            
        $tagname = 'Radfahren';

        $tag = core_tag_tag::get_by_name(0, $tagname, 'id');

        $taggeditems = $DB->get_records('tag_instance', [
            'tagid' => $tag->id,
            'component' => 'local_wb_news',
            'itemtype' => 'local_wb_news',
        ]);

        $newsids = array_values(array_unique(array_map(fn($item) => $item->itemid, $taggeditems)));

        $allinstances = news::return_all_instances();

        foreach ($allinstances as &$instance) {
            if (!isset($instance['news'])) {
                continue;
            }

            $instance['news'] = array_filter($instance['news'], function ($n) use ($newsids) {
                return in_array($n['id'], $newsids);
            });
        }
        $filteredinstances = array_filter($allinstances, function ($i) {
            return !empty($i['news']);
        });

        return [
            'instances' => $filteredinstances,
        ];

    }


    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return $this->return_list();
    }
}
