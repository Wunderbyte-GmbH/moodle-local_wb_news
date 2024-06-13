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
     * Template string.
     *
     * @var string
     */
    private string $template = 'local_wb_news/wb_news_grid';

    /**
     * Name string.
     *
     * @var string
     */
    private string $name = '';


    /**
     * Constructor
     *
     * @param int $instanceid
     *
     */
    private function __construct(int $instanceid = 0, bool $fetchitems = true) {
        global $DB;

        $this->instanceid = $instanceid;
        // When there is no instance id, we fetch all the items from the start.
        if ($fetchitems) {
            $news = self::get_items_from_db($instanceid);

            foreach ($news as $newsitem) {
                if ($newsitem->instanceid != $instanceid) {
                    $news = self::getinstance($newsitem->instanceid ?? 0, false);
                    $news->add_news($newsitem);
                } else {
                    $this->add_news($newsitem);
                }
            }
        }
    }

    /**
     * Get singelton instance.
     *
     * @param  int $id
     * @param  bool $fetchitems
     * @return news
     */
    public static function getinstance(int $instanceid, bool $fetchitems = true) {
        // Create the instance if it doesn't exist.
        if (self::$instance[$instanceid] === null) {
            self::$instance[$instanceid] = new self($instanceid, $fetchitems);
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
        return array_map(fn($a) => (array)$a, $this->news);
    }

    /**
     * Returns a the template string.
     *
     * @return string
     *
     */
    public function return_template() {
        return $this->template;
    }

    /**
     * Returns a the name string.
     *
     * @return string
     *
     */
    public function return_name() {
        return $this->name;
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
        $data->timemodified = time();

        if ($id) {
            $DB->update_record('local_wb_news', $data, true);
            return true;
        } else {
            $data->timecreated = time();
            $id = $DB->insert_record('local_wb_news', $data, true);
        }
        return $id;
    }

    /**
     * Delete news. On failure, return 0, else id of deleted record.
     *
     * @param stdClass $data
     */
    public function add_news(stdClass $data) {

        $this->news[$data->id] = $data;
        if (!empty($data->template)) {
            $this->template = $data->template;
        }
        if (!empty($data->name)) {
            $this->name = $data->name;
        }
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
     * Update or Create newsinstance
     *
     * @param stdClass $data
     * @return int $id
     */
    public function update_newsinstance($data) {
        global $DB, $USER;

        $id = $data->id ?? false;

        $data->userid = $USER->id;
        $data->timemodified = time();

        if ($id) {
            $DB->update_record('local_wb_news_instance', $data, true);
            return true;
        } else {
            $data->timecreated = time();
            $id = $DB->insert_record('local_wb_news_instance', $data, true);
        }
        return $id;
    }

    /**
     * Delete newsinstance. On failure, return 0, else id of deleted record.
     *
     * @param stdClass $data
     * @return int $id
     */
    public function delete_newsinstance($data) {
        global $DB, $USER;

        if (!empty($data->id)) {
            $DB->delete_records('local_wb_news_instance', ['id' => $data->id]);
            return $data->id;
        }

        return 0;
    }

    /**
     * Returns the instance as a renderable array.
     *
     * @return array
     *
     */
    public function return_instance() {

        global $PAGE;

        $instanceitem = [
            'instanceid' => $this->instanceid,
            'template' => $this->template,
            'name' => $this->name,
            'editmode' => $PAGE->user_is_editing(),
        ];

        if (!empty($this->news)) {
            $instanceitem['news'] = $this->return_list_of_news();
        }

        switch ($this->template) {
            case 'local_wb_news/wb_news_masonry':
                $instanceitem['masonrytemplate'] = true;
                break;
            case 'local_wb_news/wb_news_grid':
                $instanceitem['gridtemplate'] = true;
                break;
            case 'local_wb_news/wb_news_slider':
                $instanceitem['slidertemplate'] = true;
                break;
            case 'local_wb_news/wb_news_tabs':
                $instanceitem['tabstemplate'] = true;
                break;
        }

        return $instanceitem;
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

    /**
     * Fetch Items from DB.
     *
     * @param int $instanceid
     *
     * @return [type]
     *
     */
    private static function get_items_from_db(int $instanceid) {

        global $DB;

        $sql = "SELECT " . $DB->sql_concat("wni.id", "'-'", "COALESCE(wn.id, '')") . " as ident, wn.*, wni.id as instanceid, wni.template, wni.name
        FROM {local_wb_news} wn
        RIGHT JOIN {local_wb_news_instance} wni ON wni.id = wn.instanceid";

        if (!empty($id)) {
            $params = ['instanceid' => $instanceid];
            $sql .= " WHERE wni.id =:instanceid";
        } else {
            $params = [];
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Return all instances.
     *
     * @return array
     *
     */
    public static function return_all_instances() {

        global $PAGE;

        $returnarray = [];
        foreach (self::$instance as $instance) {
            if (empty($instance->instanceid)) {
                continue;
            }

            $instanceitem = $instance->return_instance();

            $returnarray[] = $instanceitem;
        }

        return $returnarray;
    }
}
