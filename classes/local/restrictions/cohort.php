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
 * Cohort-membership restriction handler.
 *
 * @package   local_wb_news
 * @copyright 2024 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_news\local\restrictions;

use MoodleQuickForm;
use stdClass;

/**
 * Restricts news-item visibility to members of one or more cohorts.
 *
 * JSON shape contributed to the restrictions field:
 * {
 *   "cohorts":      [<cohort-id>, ...],
 *   "cohortsmatch": "any" | "all"
 * }
 */
class cohort extends base {
    /** @var string user must be in at least one of the selected cohorts */
    public const MATCH_ANY = 'any';

    /** @var string user must be in every one of the selected cohorts */
    public const MATCH_ALL = 'all';

    /**
     * {@inheritdoc}
     */
    public static function get_key(): string {
        return 'cohorts';
    }

    /**
     * {@inheritdoc}
     *
     * @param MoodleQuickForm $mform the form to add fields to
     */
    public function add_form_fields(MoodleQuickForm $mform): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $mform->addElement(
            'autocomplete',
            'restrictioncohorts',
            get_string('restrictioncohorts', 'local_wb_news'),
            $this->get_cohort_options(),
            [
                'multiple' => true,
                'noselectionstring' => get_string('restrictionnocohorts', 'local_wb_news'),
            ]
        );
        $mform->setType('restrictioncohorts', PARAM_RAW);
        $mform->addHelpButton('restrictioncohorts', 'restrictioncohorts', 'local_wb_news');

        $mform->addElement(
            'select',
            'restrictioncohortsmatch',
            get_string('restrictioncohortsmatch', 'local_wb_news'),
            [
                self::MATCH_ANY => get_string('restrictioncohortsmatchany', 'local_wb_news'),
                self::MATCH_ALL => get_string('restrictioncohortsmatchall', 'local_wb_news'),
            ]
        );
        $mform->setType('restrictioncohortsmatch', PARAM_ALPHA);
        $mform->setDefault('restrictioncohortsmatch', self::MATCH_ANY);
    }

    /**
     * {@inheritdoc}
     *
     * @param stdClass $data submitted form data
     * @return array restriction key/value pairs, or empty array if no cohorts selected
     */
    public function process_form_data(stdClass $data): array {
        $cohortids = array_values(array_unique(array_filter(
            array_map('intval', (array)($data->restrictioncohorts ?? [])),
            static fn($id) => $id > 0
        )));

        if (empty($cohortids)) {
            return [];
        }

        $match = ($data->restrictioncohortsmatch ?? '') === self::MATCH_ALL
            ? self::MATCH_ALL
            : self::MATCH_ANY;

        return [
            'cohorts'      => $cohortids,
            'cohortsmatch' => $match,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param stdClass $data form data object to populate with defaults
     * @param array    $restrictions decoded restrictions array for this item
     */
    public function set_form_defaults(stdClass $data, array $restrictions): void {
        $data->restrictioncohorts = array_map('intval', (array)($restrictions['cohorts'] ?? []));
        $data->restrictioncohortsmatch = ($restrictions['cohortsmatch'] ?? '') === self::MATCH_ALL
            ? self::MATCH_ALL
            : self::MATCH_ANY;
    }

    /**
     * {@inheritdoc}
     *
     * @param stdClass $user         the user record to evaluate
     * @param array    $restrictions decoded restrictions array for this item
     * @return bool true if the user satisfies the cohort restriction
     */
    public function user_matches(stdClass $user, array $restrictions): bool {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohortids = $restrictions['cohorts'] ?? [];

        if (empty($cohortids)) {
            return true;
        }

        if (empty($user->id) || isguestuser($user)) {
            return false;
        }

        $cohortids = array_filter(
            array_map('intval', (array)$cohortids),
            static fn($id) => $id > 0
        );
        $match = ($restrictions['cohortsmatch'] ?? '') === self::MATCH_ALL
            ? self::MATCH_ALL
            : self::MATCH_ANY;

        if ($match === self::MATCH_ALL) {
            foreach ($cohortids as $cohortid) {
                if (!cohort_is_member($cohortid, $user->id)) {
                    return false;
                }
            }
            return true;
        }

        foreach ($cohortids as $cohortid) {
            if (cohort_is_member($cohortid, $user->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the options array for the cohort autocomplete element.
     *
     * @return array  [cohort-id => "Name (Context)"]
     */
    private function get_cohort_options(): array {
        $options = [];
        $results = cohort_get_all_cohorts(0, 0, '');

        foreach ($results['cohorts'] as $cohortobj) {
            $context = \context::instance_by_id($cohortobj->contextid, IGNORE_MISSING);
            if (!$context) {
                continue;
            }
            $contextname = $context->get_context_name(false, true);
            $options[$cohortobj->id] = format_string($cohortobj->name) . ' (' . $contextname . ')';
        }

        return $options;
    }
}
