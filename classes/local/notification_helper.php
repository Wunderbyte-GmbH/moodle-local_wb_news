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

namespace local_wb_news\local;

use context_system;
use local_wb_news\local\restrictions\manager as restrictions_manager;
use stdClass;

/**
 * Shared logic for the "new news entry" notifications.
 *
 * @package   local_wb_news
 * @copyright 2026 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_helper {
    /** @var string Name of the message provider. */
    public const PROVIDER = 'newnews';

    /** @var int Seconds an entry stays unsent after creation, so it can still be corrected. */
    public const SEND_DELAY = 30 * MINSECS;

    /**
     * Whether notifications are switched on in the plugin settings.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return !empty(get_config('local_wb_news', 'notificationsenabled'));
    }

    /**
     * Return the ids of the instances selected in the plugin settings.
     *
     * @return int[]
     */
    public static function get_instance_ids(): array {
        $value = (string) get_config('local_wb_news', 'notificationinstances');
        return array_values(array_filter(array_map('intval', explode(',', $value))));
    }

    /**
     * Return a news item if it still exists and belongs to a selected instance.
     *
     * Deleted items have a negative instanceid, so they never match the join.
     *
     * @param int $newsid
     * @return stdClass|null
     */
    public static function get_news_item(int $newsid): ?stdClass {
        global $DB;

        $item = $DB->get_record_sql(
            "SELECT wn.*
               FROM {local_wb_news} wn
               JOIN {local_wb_news_instance} wni ON wni.id = wn.instanceid
              WHERE wn.id = :id",
            ['id' => $newsid]
        );
        if (!$item || !in_array((int) $item->instanceid, self::get_instance_ids(), true)) {
            return null;
        }
        return $item;
    }

    /**
     * Return the users who may read the news item and should be notified about it.
     *
     * @param stdClass $item News record.
     * @return int[]
     */
    public static function get_recipient_ids(stdClass $item): array {
        $users = get_users_by_capability(
            context_system::instance(),
            'local/wb_news:view',
            'u.id, u.suspended, u.confirmed'
        );

        $userids = [];
        foreach ($users as $user) {
            if (!$user->suspended && $user->confirmed && restrictions_manager::user_matches($item, $user)) {
                $userids[] = (int) $user->id;
            }
        }
        return $userids;
    }
}
