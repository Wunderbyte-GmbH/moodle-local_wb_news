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

use context_system;
use core\message\message;
use local_wb_news\local\notification_helper;
use local_wb_news\local\restrictions\manager as restrictions_manager;
use moodle_url;
use stdClass;

/**
 * Sends the notification about one news entry to one user.
 *
 * Runs as the recipient (set_userid), so filters and strings use the recipient's language.
 *
 * @package   local_wb_news
 * @copyright 2026 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_news_notification extends \core\task\adhoc_task {
    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sendnewsnotification', 'local_wb_news');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        if (!notification_helper::is_enabled()) {
            return;
        }

        $user = \core_user::get_user($this->get_userid());
        if (!$user || $user->deleted || $user->suspended) {
            return;
        }

        // Re-read at send time: the entry or the user's access may have changed since queueing.
        $item = notification_helper::get_news_item((int) ($this->get_custom_data()->newsid ?? 0));
        if (
            !$item
            || !has_capability('local/wb_news:view', context_system::instance(), $user)
            || !restrictions_manager::user_matches($item, $user)
        ) {
            return;
        }

        $message = $this->build_message($user, $item);

        // Skip if this user already got this entry (e.g. task retried).
        $sql = $DB->sql_compare_text('customdata', 255) . ' = ' . $DB->sql_compare_text(':match', 255) . '
            AND useridto = :userid AND component = :component AND eventtype = :eventtype';
        $alreadysent = $DB->record_exists_select('notifications', $sql, [
            'match' => $message->customdata,
            'userid' => $user->id,
            'component' => 'local_wb_news',
            'eventtype' => notification_helper::PROVIDER,
        ]);
        if ($alreadysent) {
            return;
        }

        message_send($message);
    }

    /**
     * Build the message: the headline is the subject, the description is the body.
     *
     * @param stdClass $user
     * @param stdClass $item News record.
     * @return message
     */
    private function build_message(stdClass $user, stdClass $item): message {
        $syscontext = context_system::instance();

        $subject = format_string($item->headline, true, ['context' => $syscontext, 'escape' => false]);
        $description = file_rewrite_pluginfile_urls(
            $item->description,
            'pluginfile.php',
            $syscontext->id,
            'local_wb_news',
            'description',
            $item->id
        );
        $bodyhtml = format_text($description, $item->descriptionformat, ['context' => $syscontext]);
        $bodyplain = html_to_text($bodyhtml);

        $message = new message();
        $message->component = 'local_wb_news';
        $message->name = notification_helper::PROVIDER;
        $message->courseid = SITEID;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->notification = 1;
        $message->subject = $subject;
        $message->smallmessage = trim($bodyplain) === '' ? $subject : shorten_text($bodyplain, 300);
        $message->fullmessage = $bodyplain;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $bodyhtml;
        $url = new moodle_url('/local/wb_news/newsview.php', ['id' => $item->id, 'instanceid' => $item->instanceid]);
        $message->contexturl = $url->out(false);
        $message->contexturlname = get_string('notificationcontexturlname', 'local_wb_news');
        $message->customdata = ['newsid' => (int) $item->id];

        return $message;
    }
}
