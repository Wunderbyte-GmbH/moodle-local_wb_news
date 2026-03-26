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
 * Behat.
 *
 * @package    local_wb_news
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
use Behat\Gherkin\Node\TableNode;

/**
 * Behat functions.
 *
 * Custom behat steps for local_wb_news.
 *
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_wb_news extends behat_base {
    /**
     * Add a news instance to a Moodle Page as the content.
     *
     * @param string $instancename
     * @param string $pagename
     * @Given /^News instance "([^"]*)" has been added to the Page resource "([^"]*)"$/
     */
    public function i_add_news_instance_to_a_page(string $instancename, string $pagename) {
        global $DB;

        if (!$instanceid = $DB->get_field('local_wb_news_instance', 'id', ['name' => $instancename])) {
            throw new Exception('The specified news instance with name "' . $instancename . '" does not exist');
        }
        if (!$pagerecord = $DB->get_record('page', ['name' => $pagename], '*', MUST_EXIST)) {
            throw new Exception('The specified page instance with name "' . $pagename . '" does not exist');
        }
        $pagerecord->content = "[wbnews instance=" . $instanceid . "]";
        $DB->update_record('page', $pagerecord);
    }

    /**
     * Embed a news instance as an HTML block on the site home page.
     *
     * The block is inserted directly into the database so that no browser
     * interaction is required. The shortcode [wbnews instance=ID] is placed
     * inside an HTML block in the 'side-pre' region of the site home.
     *
     * @param string $instancename name of the local_wb_news_instance record
     * @Given /^news instance "([^"]*)" has been added as an HTML block on the site home$/
     */
    public function news_instance_added_as_html_block_on_site_home(string $instancename): void {
        global $DB;

        if (!$instanceid = $DB->get_field('local_wb_news_instance', 'id', ['name' => $instancename])) {
            throw new \Exception('The specified news instance with name "' . $instancename . '" does not exist');
        }

        $context = \context_course::instance(SITEID);

        $config         = new \stdClass();
        $config->text   = '[wbnews instance=' . $instanceid . ']';
        $config->format = FORMAT_HTML;

        $record                    = new \stdClass();
        $record->blockname         = 'html';
        $record->parentcontextid   = $context->id;
        $record->showinsubcontexts = 0;
        $record->pagetypepattern   = 'site-index';
        $record->subpagepattern    = null;
        $record->defaultregion     = 'side-pre';
        $record->defaultweight     = 0;
        $record->configdata        = base64_encode(serialize($config));
        $record->timecreated       = time();
        $record->timemodified      = time();
        $DB->insert_record('block_instances', $record);
    }

    /**
     * Remove a user from a cohort.
     *
     * @param string $username username of the user to remove
     * @param string $cohortidnumber idnumber of the cohort
     * @Given /^user "([^"]*)" is removed from cohort "([^"]*)"$/
     */
    public function user_is_removed_from_cohort(string $username, string $cohortidnumber): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $cohort = $DB->get_record('cohort', ['idnumber' => $cohortidnumber], '*', MUST_EXIST);

        cohort_remove_member($cohort->id, $user->id);
    }

    /**
     * Set a cohort restriction on one or more news items identified by headline.
     *
     * Supports a comma-separated list of cohort names for the MATCH_ALL scenario,
     * e.g. "Premium A,Premium B".
     *
     * @param string $headline    headline of the news item in the DB
     * @param string $cohortnames comma-separated cohort name(s) to restrict to
     * @param string $mode        "any" or "all"
     * @Given /^the news item "([^"]*)" has a cohort restriction for "([^"]*)" with match mode "([^"]*)"$/
     */
    public function news_item_has_cohort_restriction(
        string $headline,
        string $cohortnames,
        string $mode
    ): void {
        global $DB;

        $cohortids = [];
        foreach (array_map('trim', explode(',', $cohortnames)) as $name) {
            $cohort = $DB->get_record('cohort', ['name' => $name], '*', MUST_EXIST);
            $cohortids[] = (int) $cohort->id;
        }

        $sql = "SELECT * FROM {local_wb_news} WHERE " . $DB->sql_compare_text('headline') . " = ?";
        $newsitems = $DB->get_records_sql($sql, [$headline]);

        if (empty($newsitems)) {
            throw new \Exception("No news item with headline '$headline' found in the database.");
        }

        $mode = ($mode === 'all') ? 'all' : 'any';
        foreach ($newsitems as $newsitem) {
            $newsitem->restrictions = json_encode([
                'cohorts'      => $cohortids,
                'cohortsmatch' => $mode,
            ]);
            $DB->update_record('local_wb_news', $newsitem);
        }
    }
}
