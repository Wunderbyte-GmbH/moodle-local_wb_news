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

use stdClass;
use context_system;

/**
 * Class news.
 * @package local_wb_news
 * @author Thomas Winkler
 * @copyright 2024 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class news {

    /**
     * Array of instances.
     *
     * @var array
     */
    private static array $instance = [];

    /**
     * [Description for $instanceid]
     *
     * @var int
     */
    public $instanceid = 0;

    /**
     * Array of news.
     *
     * @var array
     */
    private array $news = [];


    /**
     * Constructor
     *
     * @param int $instanceid
     *
     */
    private function __construct(int $instanceid) {
        global $DB;

        $sql = "SELECT wn.*, wni.id as instanceid
                FROM {local_wb_news} wn
                LEFT JOIN {local_wb_news_instance} wni ON wni.id = wn.instanceid";

        if (!empty($id)) {
            $params = ['instanceid' => $instanceid];
            $sql .= " WHERE wni.id =:instanceid";
        } else {
            $params = [];
        }

        $news = $DB->get_records_sql($sql, $params);

        $this->news = array_map(fn($a) => (array)$a, $news);
    }

    /**
     * Get singelton instance.
     *
     * @param  int $id
     * @return news
     */
    public static function getinstance(int $instanceid) {
        // Create the instance if it doesn't exist.
        if (self::$instance[$instanceid] === null) {
            self::$instance[$instanceid] = new self($instanceid);
        }
        return self::$instance[$instanceid];
    }

    /**
     * Returns a list of news from the current instance.
     *
     * @return array
     *
     */
    public function return_list_of_news() {
        return $this->news;
    }

    /**
     * Returns a list of news from the current instance.
     *
     * @param int $id
     * @return stdClass|null
     *
     */
    public function get_news_item($id) {
        return $this->news[$id] ?? null;
    }

    /**
     * Update or Create news
     *
     * @param stdClass $data
     * @return int $id
     */
    public function update_news($data) {
        global $DB, $USER;

        $id = $data->id ?? false;

        $data->userid = $USER->id;
        if ($id) {
            $DB->update_record('local_wb_news', $data, true);
            return true;
        } else {
            $id = $DB->insert_record('local_wb_news', $data, true);
        }
        return $id;
    }

    /**
     * Delete news. On failure, return 0, else id of deleted record.
     *
     * @param stdClass $data
     * @return int $id
     */
    public function delete_news($data) {
        global $DB, $USER;

        if (!empty($data->id)) {
            $DB->delete_records('local_wb_news', ['id' => $data->id]);
            return $data->id;
        }

        return 0;
    }

    /**
     * As we need it twice, we create a function.
     * @return array
     */
    public static function get_textfield_options() {

        $context = context_system::instance();

        return [
            'trusttext' => true,
            'subdirs' => true,
            'context' => $context,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,
        ];
    }
}
