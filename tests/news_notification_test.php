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

use advanced_testcase;
use local_wb_news\task\queue_news_notifications;
use local_wb_news\task\send_news_notification;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the "new news entry" notifications.
 *
 * @package   local_wb_news
 * @category  test
 * @copyright 2026 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wb_news\task\queue_news_notifications
 * @covers \local_wb_news\task\send_news_notification
 * @covers \local_wb_news\local\notification_helper
 */
final class news_notification_test extends advanced_testcase {
    /**
     * Reset the database, switch notifications on and record the first task run.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('notificationsenabled', 1, 'local_wb_news');
        (new queue_news_notifications())->execute();
    }

    /**
     * Create a news instance.
     *
     * @return int
     */
    private function create_instance(): int {
        global $DB;
        return (int) $DB->insert_record('local_wb_news_instance', [
            'name' => 'Instance',
            'template' => 'local_wb_news/wb_news_grid',
            'userid' => 2,
            'columns' => 4,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Create a news item.
     *
     * @param int $instanceid
     * @param string|null $restrictions JSON
     * @param int $age Seconds since creation; by default the send delay has passed.
     * @return int
     */
    private function create_item(int $instanceid, ?string $restrictions = null, int $age = 31 * MINSECS): int {
        global $DB;
        return (int) $DB->insert_record('local_wb_news', [
            'instanceid' => $instanceid,
            'userid' => 2,
            'headline' => 'The headline',
            'subheadline' => '',
            'description' => '<p>The text</p>',
            'descriptionformat' => FORMAT_HTML,
            'json' => '{}',
            'restrictions' => $restrictions,
            'timecreated' => time() - $age,
            'timemodified' => time() - $age,
        ]);
    }

    /**
     * Run the scheduled task and the queued sends, and return the messages sent to a user.
     *
     * @param int $userid
     * @return \stdClass[]
     */
    private function run_tasks_for(int $userid): array {
        $sink = $this->redirectMessages();
        ob_start();
        (new queue_news_notifications())->execute();
        $this->runAdhocTasks(send_news_notification::class);
        ob_end_clean();
        $messages = $sink->get_messages();
        $sink->close();
        return array_values(array_filter($messages, fn($message) => (int) $message->useridto === $userid));
    }

    /**
     * Select instances in the plugin setting.
     *
     * @param int[] $instanceids
     */
    private function select_instances(array $instanceids): void {
        set_config('notificationinstances', implode(',', $instanceids), 'local_wb_news');
    }

    public function test_message_provider_is_registered(): void {
        global $DB;
        $this->assertTrue($DB->record_exists('message_providers', ['component' => 'local_wb_news', 'name' => 'newnews']));
    }

    public function test_scheduled_task_is_registered(): void {
        $this->assertNotNull(\core\task\manager::get_scheduled_task(queue_news_notifications::class));
    }

    public function test_setting_lists_instances_and_saved_value_is_used(): void {
        $this->setAdminUser();
        $instanceid = $this->create_instance();
        $other = $this->create_instance();

        $page = admin_get_root(true, true)->locate('local_wb_news');
        $setting = $page->settings->local_wb_newsnotificationinstances;
        $this->assertArrayHasKey($instanceid, $setting->choices);

        $this->assertSame('', $setting->write_setting([(string) $instanceid, (string) $other]));
        $this->assertSame(
            [$instanceid, $other],
            \local_wb_news\local\notification_helper::get_instance_ids()
        );
    }

    public function test_selected_instance_sends_headline_and_text(): void {
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $itemid = $this->create_item($instanceid);

        $messages = $this->run_tasks_for((int) $user->id);

        $this->assertCount(1, $messages);
        $this->assertSame('local_wb_news', $messages[0]->component);
        $this->assertSame('newnews', $messages[0]->eventtype);
        $this->assertSame('The headline', $messages[0]->subject);
        $this->assertStringContainsString('The text', $messages[0]->fullmessagehtml);
        $this->assertStringContainsString('The text', $messages[0]->fullmessage);
        $this->assertStringContainsString("newsview.php?id=$itemid", $messages[0]->contexturl);
    }

    public function test_entry_waits_for_send_delay_and_sends_corrected_text(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $itemid = $this->create_item($instanceid, null, 29 * MINSECS);

        $this->assertCount(0, $this->run_tasks_for((int) $user->id));
        $this->assertFalse($DB->record_exists('task_adhoc', ['classname' => '\\' . send_news_notification::class]));

        $DB->update_record('local_wb_news', [
            'id' => $itemid,
            'headline' => 'Fixed headline',
            'description' => '<p>Fixed text</p>',
            'timecreated' => time() - 31 * MINSECS,
        ]);

        $messages = $this->run_tasks_for((int) $user->id);
        $this->assertCount(1, $messages);
        $this->assertSame('Fixed headline', $messages[0]->subject);
        $this->assertStringContainsString('Fixed text', $messages[0]->fullmessagehtml);
    }

    public function test_entry_deleted_during_send_delay_is_not_sent(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $itemid = $this->create_item($instanceid, null, 29 * MINSECS);
        $this->assertCount(0, $this->run_tasks_for((int) $user->id));

        $DB->update_record('local_wb_news', [
            'id' => $itemid,
            'instanceid' => -$instanceid,
            'timecreated' => time() - 31 * MINSECS,
        ]);

        $this->assertCount(0, $this->run_tasks_for((int) $user->id));
    }

    public function test_waiting_entry_is_not_skipped_by_a_later_due_entry(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $waitingid = $this->create_item($instanceid, null, 10 * MINSECS);
        $this->create_item($instanceid);

        $this->assertCount(0, $this->run_tasks_for((int) $user->id));

        $DB->set_field('local_wb_news', 'timecreated', time() - 31 * MINSECS, ['id' => $waitingid]);
        $this->assertCount(2, $this->run_tasks_for((int) $user->id));
    }

    public function test_setting_is_off_by_default(): void {
        $this->setAdminUser();
        $page = admin_get_root(true, true)->locate('local_wb_news');
        $this->assertSame(0, $page->settings->local_wb_newsnotificationsenabled->get_defaultsetting());
    }

    public function test_switched_off_sends_nothing_and_no_backlog_when_switched_on(): void {
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        set_config('notificationsenabled', 0, 'local_wb_news');
        $this->create_item($instanceid);

        $this->assertCount(0, $this->run_tasks_for((int) $user->id));

        set_config('notificationsenabled', 1, 'local_wb_news');
        $this->assertCount(0, $this->run_tasks_for((int) $user->id));

        $this->create_item($instanceid);
        $this->assertCount(1, $this->run_tasks_for((int) $user->id));
    }

    public function test_switching_off_stops_queued_messages(): void {
        $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $this->create_item($instanceid);

        ob_start();
        (new queue_news_notifications())->execute();
        ob_end_clean();
        $this->assertNotEmpty(\core\task\manager::get_adhoc_tasks(send_news_notification::class));
        set_config('notificationsenabled', 0, 'local_wb_news');

        $sink = $this->redirectMessages();
        ob_start();
        $this->runAdhocTasks(send_news_notification::class);
        ob_end_clean();
        $this->assertSame(0, $sink->count());
        $sink->close();
    }

    public function test_unselected_instance_sends_nothing(): void {
        $user = $this->getDataGenerator()->create_user();
        $selected = $this->create_instance();
        $this->select_instances([$selected]);
        $this->create_item($this->create_instance());

        $this->assertCount(0, $this->run_tasks_for((int) $user->id));
    }

    public function test_existing_entries_are_not_sent_on_first_run(): void {
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $this->create_item($instanceid);
        unset_config('notificationlastnewsid', 'local_wb_news');

        $this->assertCount(0, $this->run_tasks_for((int) $user->id));
    }

    public function test_selecting_an_instance_later_does_not_send_older_entries(): void {
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->create_item($instanceid);
        $this->assertCount(0, $this->run_tasks_for((int) $user->id));

        $this->select_instances([$instanceid]);
        $this->assertCount(0, $this->run_tasks_for((int) $user->id));

        $this->create_item($instanceid);
        $this->assertCount(1, $this->run_tasks_for((int) $user->id));
    }

    public function test_entry_is_sent_only_once(): void {
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $this->create_item($instanceid);

        $this->assertCount(1, $this->run_tasks_for((int) $user->id));
        $this->assertCount(0, $this->run_tasks_for((int) $user->id));
    }

    public function test_retried_send_does_not_duplicate(): void {
        $user = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $itemid = $this->create_item($instanceid);

        $task = new send_news_notification();
        $task->set_userid((int) $user->id);
        $task->set_custom_data(['newsid' => $itemid]);

        $sink = $this->redirectMessages();
        $task->execute();
        $task->execute();
        $this->assertSame(1, $sink->count());
        $sink->close();
    }

    public function test_cohort_restriction_limits_recipients(): void {
        $member = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $member->id);

        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $this->create_item($instanceid, json_encode(['cohorts' => [(int) $cohort->id], 'cohortsmatch' => 'any']));

        $sink = $this->redirectMessages();
        ob_start();
        (new queue_news_notifications())->execute();
        $this->runAdhocTasks(send_news_notification::class);
        ob_end_clean();
        $recipients = array_map(fn($message) => (int) $message->useridto, $sink->get_messages());
        $sink->close();

        $this->assertContains((int) $member->id, $recipients);
        $this->assertNotContains((int) $other->id, $recipients);
    }

    public function test_suspended_user_gets_nothing(): void {
        $suspended = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $active = $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $this->create_item($instanceid);

        $sink = $this->redirectMessages();
        ob_start();
        (new queue_news_notifications())->execute();
        $this->runAdhocTasks(send_news_notification::class);
        ob_end_clean();
        $recipients = array_map(fn($message) => (int) $message->useridto, $sink->get_messages());
        $sink->close();

        $this->assertContains((int) $active->id, $recipients);
        $this->assertNotContains((int) $suspended->id, $recipients);
    }

    public function test_entry_deleted_after_queueing_is_not_sent(): void {
        global $DB;
        $this->getDataGenerator()->create_user();
        $instanceid = $this->create_instance();
        $this->select_instances([$instanceid]);
        $itemid = $this->create_item($instanceid);

        ob_start();
        (new queue_news_notifications())->execute();
        ob_end_clean();
        $this->assertNotEmpty(\core\task\manager::get_adhoc_tasks(send_news_notification::class));
        $DB->set_field('local_wb_news', 'instanceid', -$instanceid, ['id' => $itemid]);

        $sink = $this->redirectMessages();
        ob_start();
        $this->runAdhocTasks(send_news_notification::class);
        ob_end_clean();
        $this->assertSame(0, $sink->count());
        $sink->close();
    }
}
