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

use local_wb_news\news;
use renderable;
use renderer_base;
use templatable;

/**
 * viewtable class to display view.php
 * @package local_wb_news
 *
 */
class wb_news implements renderable, templatable {

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
     * Constructor.
     *
     * @param int $instanceid = 0;
     */
    public function __construct(int $instanceid) {

        $news = news::getinstance($instanceid);

        $this->news = $news->return_list_of_news();
    }

    /**
     * Returns the items of this class.
     *
     * @return array
     *
     */
    public function return_list() {

        if (empty($this->news)) {
            return [];
        }

        return [
            'news' => $this->news,
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
