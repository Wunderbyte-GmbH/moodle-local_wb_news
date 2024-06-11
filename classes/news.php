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

namespace local_wb_news;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

use core_course_category;
use stdClass;

/**
 * Class news.
 * @package local_wb_news
 * @author Thomas Winkler
 * @copyright 2024 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news {

    public $schooltypes;

    private static array $instance;

    // The constructor is private to prevent direct creation of object
    private function __construct(int $id) {
        global $DB;
        $sql = "SELECT * FROM {local_wb_news} wn
                JOIN {local_wb_news_instance} wni ON wni.id = wn.instanceid
                WHERE wni.id = ?";

        $news = $DB->get_records_sql($sql, [$id], true);
        $this->instance[$id] = $news;
    }

    /**
     * Get singelton
     *
     * @param  integer $id
     *
     * @return void
     */
    public static function getinstance(int $id) {
        // Create the instance if it doesn't exist
        if (self::$instance[$id] === null) {
            self::$instance[$id] = new self($id);
        }
        return self::$instance[$id];
    }

    public function formfields(&$mform) {

    }

    // TODO Replace with setting manager.
    /**
     * Update or Create news
     *
     * @param stdClass|array $data
     * @return int $id
     */
    public function update_news($data) {
        global $DB;
        if ($data->id) {
            $DB->update_record('local_wb_news', $data, true);
            return true;
        } else {
            $DB->insert_record('local_wb_news', $data, true);
        }
    }
}
