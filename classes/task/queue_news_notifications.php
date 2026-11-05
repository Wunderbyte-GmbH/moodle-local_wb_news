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

namespace local_wb_news\task;

use local_wb_news\local\notification_helper;

/**
 * Finds news entries that have passed the send delay and queues one notification per recipient.
 *
 * Progress is kept as the highest news id handled, and it also moves on while no instance is
 * selected or notifications are switched off. Turning either on therefore does not notify about
 * entries handled before that.
 *
 * @package   local_wb_news
 * @copyright 2026 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_news_notifications extends \core\task\scheduled_task {
    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_queuenewsnotifications', 'local_wb_news');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        $lastid = get_config('local_wb_news', 'notificationlastnewsid');
        $maxid = (int) $DB->get_field_sql('SELECT MAX(id) FROM {local_wb_news}');

        // First run: entries that already exist are not new.
        if ($lastid === false) {
            set_config('notificationlastnewsid', $maxid, 'local_wb_news');
            return;
        }

        // Stop before the first entry still inside the send delay, so it is picked up by a later run.
        $lastid = (int) $lastid;
        $firstwaiting = (int) $DB->get_field_sql(
            'SELECT MIN(id) FROM {local_wb_news} WHERE id > :lastid AND timecreated > :cutoff',
            ['lastid' => $lastid, 'cutoff' => time() - notification_helper::SEND_DELAY]
        );
        $uptoid = $firstwaiting ? $firstwaiting - 1 : $maxid;
        if ($uptoid <= $lastid) {
            return;
        }

        $instanceids = notification_helper::get_instance_ids();
        if ($instanceids && notification_helper::is_enabled()) {
            [$insql, $params] = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
            $params['lastid'] = $lastid;
            $params['uptoid'] = $uptoid;
            $items = $DB->get_records_sql(
                "SELECT wn.id, wn.instanceid, wn.restrictions
                   FROM {local_wb_news} wn
                   JOIN {local_wb_news_instance} wni ON wni.id = wn.instanceid
                  WHERE wn.id > :lastid AND wn.id <= :uptoid AND wn.instanceid $insql
               ORDER BY wn.id",
                $params
            );

            foreach ($items as $item) {
                $userids = notification_helper::get_recipient_ids($item);
                foreach ($userids as $userid) {
                    $task = new send_news_notification();
                    $task->set_userid($userid);
                    $task->set_custom_data(['newsid' => (int) $item->id]);
                    \core\task\manager::queue_adhoc_task($task, true);
                }
                mtrace('Queued ' . count($userids) . " notifications for news entry $item->id.");
            }
        }

        set_config('notificationlastnewsid', $uptoid, 'local_wb_news');
    }
}
