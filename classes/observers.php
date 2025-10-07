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

class observers {
    public static function course_completed(\core\event\course_completed $event) {
        global $DB, $USER;

        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        $data = $DB->get_record('customfield_data', [
            'instanceid' => $courseid
        ]);

        if (!$data || empty($data->value)) {
            debugging("Kein Zielkurs im Custom Field gefunden", DEBUG_DEVELOPER);
            return;
        }

        $targetcourseid = (int)$data->value;

        require_once($GLOBALS['CFG']->dirroot.'/enrol/manual/locallib.php');

        $enrol = enrol_get_plugin('manual');
        if ($instances = enrol_get_instances($targetcourseid, true)) {
            foreach ($instances as $instance) {
                if ($instance->enrol === 'manual') {
                    $enrol->enrol_user($instance, $userid, null, time());
                    debugging("User $userid in Kurs $targetcourseid eingeschrieben", DEBUG_DEVELOPER);
                    return;
                }
            }
        }
    }
}
