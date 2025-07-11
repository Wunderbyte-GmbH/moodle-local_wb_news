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

use local_wb_news\news;

/**
 * Class local_wb_news_generator for generation of dummy data
 *
 * @package local_wb_news
 * @category test
 * @copyright 2025 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wb_news_generator extends testing_module_generator {
    /**
     * Function to create a dummy news instance.
     *
     * @param array|stdClass $record
     * @return stdClass the news instance object
     */
    public function create_news_instance($record = null) {
        global $DB;

        $record = (object) $record;

        // Converst array of names to context IDs.
        $record->contextids = [];
        $contexts = array_map('trim', explode(',', $record->contexts));
        $categories = news::get_contextid_options();
        foreach ($contexts as $context) {
            if (($contextid = array_search($context, $categories)) !== false) {
                $record->contextids[] = $contextid;
            }
        }

        // Create the news instance.
        $news = news::getinstance(0);
        $news->update_newsinstance($record);

        return $record;
    }
}
