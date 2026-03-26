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
 * PHPUnit tests for the wb_news visibility restrictions system.
 *
 * These tests cover:
 *   - manager::decode_restrictions()   — edge-case JSON handling
 *   - manager::build_restrictions_json() — form-data → JSON round-trip
 *   - cohort::user_matches()            — all any/all × in/out permutations
 *   - news::can_user_see_news_item()    — blocked, allowed, capability bypass
 *
 * Tests are designed to FAIL if the restriction logic is removed or broken.
 *
 * @package   local_wb_news
 * @category  test
 * @copyright 2024 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_news;

use advanced_testcase;
use local_wb_news\local\restrictions\cohort as cohort_restriction;
use local_wb_news\local\restrictions\manager;
use local_wb_news\news;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Test suite for the restrictions subsystem.
 *
 * @coversDefaultClass \local_wb_news\local\restrictions\manager
 * @covers \local_wb_news\local\restrictions\cohort
 * @covers \local_wb_news\news
 */
final class restrictions_test extends advanced_testcase {
    /**
     * Reset static caches that persist across unit tests.
     *
     * The news singleton and the manager handler cache both use static
     * properties which must be cleared between tests to prevent cross-test
     * contamination.
     */
    private function clear_caches(): void {
        $r = new \ReflectionClass(manager::class);
        $p = $r->getProperty('handlers');
        $p->setAccessible(true);
        $p->setValue(null, null);

        $r2 = new \ReflectionClass(news::class);
        $p2 = $r2->getProperty('instance');
        $p2->setAccessible(true);
        $p2->setValue(null, []);
    }

    /**
     * Insert a minimal news instance directly into the DB and return its ID.
     */
    private function db_create_instance(): int {
        global $DB;
        return (int) $DB->insert_record('local_wb_news_instance', [
            'name'         => 'UnitTestInstance-' . uniqid(),
            'template'     => 'local_wb_news/wb_news_grid',
            'userid'       => 1,
            'columns'      => 4,
            'timecreated'  => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Insert a minimal news item directly into the DB and return its ID.
     *
     * @param int         $instanceid
     * @param string|null $restrictionsjson
     */
    private function db_create_news_item(int $instanceid, ?string $restrictionsjson = null): int {
        global $DB;
        return (int) $DB->insert_record('local_wb_news', [
            'instanceid'        => $instanceid,
            'userid'            => 1,
            'headline'          => 'Test Headline ' . uniqid(),
            'subheadline'       => '',
            'description'       => '',
            'descriptionformat' => 0,
            'json'              => '{}',
            'restrictions'      => $restrictionsjson,
            'timecreated'       => time(),
            'timemodified'      => time(),
        ]);
    }

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->clear_caches();
    }

    protected function tearDown(): void {
        $this->clear_caches();
        parent::tearDown();
    }

    /**
     * NULL input should return an empty array, not throw.
     */
    public function test_decode_restrictions_null_returns_empty_array(): void {
        $this->assertSame([], manager::decode_restrictions(null));
    }

    /**
     * Empty string should return an empty array.
     */
    public function test_decode_restrictions_empty_string_returns_empty_array(): void {
        $this->assertSame([], manager::decode_restrictions(''));
    }

    /**
     * Invalid JSON should return an empty array gracefully.
     */
    public function test_decode_restrictions_invalid_json_returns_empty_array(): void {
        $this->assertSame([], manager::decode_restrictions('{not valid json'));
    }

    /**
     * Valid JSON is decoded to the expected associative array.
     */
    public function test_decode_restrictions_valid_json_returned_correctly(): void {
        $json = json_encode(['cohorts' => [5, 99], 'cohortsmatch' => 'all']);
        $result = manager::decode_restrictions($json);

        $this->assertIsArray($result);
        $this->assertSame([5, 99], $result['cohorts']);
        $this->assertSame('all', $result['cohortsmatch']);
    }

    /**
     * When no cohorts are submitted the result must be NULL so that an empty
     * restrictions column is stored, not a JSON string that changes behaviour.
     */
    public function test_build_restrictions_json_returns_null_when_no_cohorts(): void {
        $data = new stdClass();
        $data->restrictioncohorts    = [];
        $data->restrictioncohortsmatch = cohort_restriction::MATCH_ANY;

        $this->assertNull(manager::build_restrictions_json($data));
    }

    /**
     * Zero or negative IDs supplied by a manipulated form must be ignored.
     */
    public function test_build_restrictions_json_ignores_invalid_cohort_ids(): void {
        $data = new stdClass();
        $data->restrictioncohorts    = [0, -1, -99];
        $data->restrictioncohortsmatch = cohort_restriction::MATCH_ANY;

        $this->assertNull(manager::build_restrictions_json($data));
    }

    /**
     * Supplying valid cohort IDs with "any" mode round-trips correctly.
     */
    public function test_build_restrictions_json_any_mode(): void {
        $data = new stdClass();
        $data->restrictioncohorts    = [3, 7];
        $data->restrictioncohortsmatch = cohort_restriction::MATCH_ANY;

        $json   = manager::build_restrictions_json($data);
        $decoded = json_decode($json, true);

        $this->assertSame([3, 7], $decoded['cohorts']);
        $this->assertSame(cohort_restriction::MATCH_ANY, $decoded['cohortsmatch']);
    }

    /**
     * Supplying valid cohort IDs with "all" mode round-trips correctly.
     */
    public function test_build_restrictions_json_all_mode(): void {
        $data = new stdClass();
        $data->restrictioncohorts    = [1, 2, 3];
        $data->restrictioncohortsmatch = cohort_restriction::MATCH_ALL;

        $json   = manager::build_restrictions_json($data);
        $decoded = json_decode($json, true);

        $this->assertSame([1, 2, 3], $decoded['cohorts']);
        $this->assertSame(cohort_restriction::MATCH_ALL, $decoded['cohortsmatch']);
    }

    /**
     * When the restrictions array contains no cohort IDs the item is public.
     */
    public function test_cohort_user_matches_passes_when_no_cohort_restriction(): void {
        $handler = new cohort_restriction();
        $user    = $this->getDataGenerator()->create_user();

        $this->assertTrue($handler->user_matches($user, []));
        $this->assertTrue($handler->user_matches($user, ['cohorts' => []]));
    }

    /**
     * Guest users must NEVER see cohort-restricted items.
     */
    public function test_cohort_user_matches_blocks_guest(): void {
        global $CFG;
        $handler = new cohort_restriction();
        $cohort  = $this->getDataGenerator()->create_cohort();
        $guest   = \core_user::get_user($CFG->siteguest);

        $restrictions = ['cohorts' => [$cohort->id], 'cohortsmatch' => cohort_restriction::MATCH_ANY];

        $this->assertFalse($handler->user_matches($guest, $restrictions));
    }

    /**
     * MATCH_ANY: user is a member of exactly one of the listed cohorts → allow.
     */
    public function test_cohort_match_any_allows_user_in_one_cohort(): void {
        $handler = new cohort_restriction();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $user    = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort1->id, $user->id);
        // User is NOT in cohort2.

        $restrictions = [
            'cohorts'      => [$cohort1->id, $cohort2->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ];
        $this->assertTrue($handler->user_matches($user, $restrictions));
    }

    /**
     * MATCH_ANY: user is a member of none of the listed cohorts → deny.
     */
    public function test_cohort_match_any_blocks_user_in_no_cohort(): void {
        $handler = new cohort_restriction();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $user    = $this->getDataGenerator()->create_user();
        // User not added to any cohort.

        $restrictions = [
            'cohorts'      => [$cohort1->id, $cohort2->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ];
        $this->assertFalse($handler->user_matches($user, $restrictions));
    }

    /**
     * MATCH_ALL: user is a member of every listed cohort → allow.
     */
    public function test_cohort_match_all_allows_user_in_all_cohorts(): void {
        $handler = new cohort_restriction();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $user    = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort1->id, $user->id);
        cohort_add_member($cohort2->id, $user->id);

        $restrictions = [
            'cohorts'      => [$cohort1->id, $cohort2->id],
            'cohortsmatch' => cohort_restriction::MATCH_ALL,
        ];
        $this->assertTrue($handler->user_matches($user, $restrictions));
    }

    /**
     * MATCH_ALL: user is missing one cohort membership → deny.
     *
     * This is the critical difference between ANY and ALL mode and must fail
     * if the logic wrongly uses OR instead of AND.
     */
    public function test_cohort_match_all_blocks_user_missing_one_cohort(): void {
        $handler = new cohort_restriction();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $user    = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort1->id, $user->id);
        // NOT added to cohort2.

        $restrictions = [
            'cohorts'      => [$cohort1->id, $cohort2->id],
            'cohortsmatch' => cohort_restriction::MATCH_ALL,
        ];
        $this->assertFalse($handler->user_matches($user, $restrictions));
    }

    /**
     * An item without a restrictions value is visible to any authenticated user.
     */
    public function test_can_user_see_unrestricted_item(): void {
        $instanceid = $this->db_create_instance();
        $newsitemid = $this->db_create_news_item($instanceid, null);
        $user       = $this->getDataGenerator()->create_user();

        $this->clear_caches();
        $newsobj = news::getinstance($instanceid);

        $this->assertTrue($newsobj->can_user_see_news_item($newsitemid, $user));
    }

    /**
     * A cohort-restricted item is NOT visible to a user outside the cohort.
     *
     * This test fails if restriction filtering is removed from can_user_see_news_item().
     */
    public function test_can_user_see_restricted_item_blocked_for_non_member(): void {
        $cohort      = $this->getDataGenerator()->create_cohort();
        $restrictions = json_encode([
            'cohorts'      => [$cohort->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ]);

        $instanceid = $this->db_create_instance();
        $newsitemid = $this->db_create_news_item($instanceid, $restrictions);
        $user       = $this->getDataGenerator()->create_user();
        // User NOT added to the cohort.

        $this->clear_caches();
        $newsobj = news::getinstance($instanceid);

        $this->assertFalse($newsobj->can_user_see_news_item($newsitemid, $user));
    }

    /**
     * A cohort-restricted item IS visible to a user who is a member.
     *
     * This test fails if the cohort membership check is broken.
     */
    public function test_can_user_see_restricted_item_allowed_for_member(): void {
        $cohort      = $this->getDataGenerator()->create_cohort();
        $restrictions = json_encode([
            'cohorts'      => [$cohort->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ]);

        $instanceid = $this->db_create_instance();
        $newsitemid = $this->db_create_news_item($instanceid, $restrictions);
        $user       = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort->id, $user->id);

        $this->clear_caches();
        $newsobj = news::getinstance($instanceid);

        $this->assertTrue($newsobj->can_user_see_news_item($newsitemid, $user));
    }

    /**
     * A user with the local/wb_news:manage capability bypasses ALL restrictions.
     *
     * This test fails if the capability check is removed from can_user_see_news_item().
     */
    public function test_can_user_see_manage_capability_bypasses_restriction(): void {
        $cohort      = $this->getDataGenerator()->create_cohort();
        $restrictions = json_encode([
            'cohorts'      => [$cohort->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ]);

        $instanceid = $this->db_create_instance();
        $newsitemid = $this->db_create_news_item($instanceid, $restrictions);

        // Create a manager user: assign the manage capability at system level.
        $manager   = $this->getDataGenerator()->create_user();
        $roleid    = $this->getDataGenerator()->create_role();
        assign_capability(
            'local/wb_news:manage',
            CAP_ALLOW,
            $roleid,
            \context_system::instance()
        );
        role_assign($roleid, $manager->id, \context_system::instance()->id);
        accesslib_clear_all_caches_for_unit_testing();
        // Manager is NOT a cohort member.

        $this->clear_caches();
        $newsobj = news::getinstance($instanceid);

        $this->assertTrue($newsobj->can_user_see_news_item($newsitemid, $manager));
    }

    /**
     * An item with no restriction JSON passes the manager check for any user.
     */
    public function test_manager_user_matches_passes_with_no_restrictions(): void {
        $newsitem               = new stdClass();
        $newsitem->restrictions = null;
        $user                   = $this->getDataGenerator()->create_user();

        $this->assertTrue(manager::user_matches($newsitem, $user));
    }

    /**
     * An item whose cohort restriction is not met fails the manager check.
     *
     * This test would pass incorrectly if manager::user_matches() stopped
     * delegating to the cohort handler.
     */
    public function test_manager_user_matches_fails_when_cohort_not_met(): void {
        $cohort = $this->getDataGenerator()->create_cohort();
        $user   = $this->getDataGenerator()->create_user();
        // User not in cohort.

        $newsitem               = new stdClass();
        $newsitem->restrictions = json_encode([
            'cohorts'      => [$cohort->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ]);

        $this->assertFalse(manager::user_matches($newsitem, $user));
    }

    /**
     * An item whose cohort restriction IS met passes the manager check.
     */
    public function test_manager_user_matches_passes_when_cohort_met(): void {
        $cohort = $this->getDataGenerator()->create_cohort();
        $user   = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort->id, $user->id);

        $newsitem               = new stdClass();
        $newsitem->restrictions = json_encode([
            'cohorts'      => [$cohort->id],
            'cohortsmatch' => cohort_restriction::MATCH_ANY,
        ]);

        $this->assertTrue(manager::user_matches($newsitem, $user));
    }

    /**
     * Values stored as JSON can be re-loaded into form defaults and saved again
     * without loss or corruption (codec round-trip).
     */
    public function test_cohort_codec_round_trip(): void {
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        // Simulate save.
        $submit = new stdClass();
        $submit->restrictioncohorts    = [$cohort1->id, $cohort2->id];
        $submit->restrictioncohortsmatch = cohort_restriction::MATCH_ALL;

        $json = manager::build_restrictions_json($submit);
        $this->assertNotNull($json);

        // Simulate load back.
        $formdata    = new stdClass();
        $restrictions = manager::decode_restrictions($json);
        manager::set_form_defaults($formdata, $restrictions);

        $this->assertSame(
            [(int)$cohort1->id, (int)$cohort2->id],
            $formdata->restrictioncohorts,
            'Cohort IDs must survive a save/reload cycle unchanged.'
        );
        $this->assertSame(
            cohort_restriction::MATCH_ALL,
            $formdata->restrictioncohortsmatch,
            'Match mode must survive a save/reload cycle unchanged.'
        );
    }
}
