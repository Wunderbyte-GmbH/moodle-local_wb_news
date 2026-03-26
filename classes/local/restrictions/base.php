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
 * Abstract base class for wb_news visibility restrictions.
 *
 * @package   local_wb_news
 * @copyright 2024 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_news\local\restrictions;

use MoodleQuickForm;
use stdClass;

/**
 * Abstract base class that every restriction type must extend.
 *
 * To add a new restriction type, create a class in this directory that extends
 * this base class and implements all abstract methods. The manager will
 * auto-discover it via the directory scan – no other changes are required.
 */
abstract class base {
    /**
     * Returns the unique JSON key used to store this restriction's data.
     *
     * @return string
     */
    abstract public static function get_key(): string;

    /**
     * Add this restriction's form elements to the given mform.
     *
     * Elements are appended directly after the Restrictions header that
     * the form already adds. The method is responsible for calling
     * addElement, setType, setDefault, addHelpButton etc.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    abstract public function add_form_fields(MoodleQuickForm $mform): void;

    /**
     * Extract restriction data from the submitted form $data object and
     * return it as a flat associative array that will be merged into the
     * restrictions JSON. Return an empty array when no restriction is active.
     *
     * @param stdClass $data submitted form data
     * @return array
     */
    abstract public function process_form_data(stdClass $data): array;

    /**
     * Populate the form $data object with default/existing values for this
     * restriction, read from the already-decoded $restrictions array.
     *
     * @param stdClass $data  form data object to populate (modified in-place)
     * @param array    $restrictions decoded restrictions array (may be empty)
     * @return void
     */
    abstract public function set_form_defaults(stdClass $data, array $restrictions): void;

    /**
     * Decide whether $user satisfies this restriction.
     *
     * Should return true when there is no relevant restriction data present
     * (i.e. the restriction is not active for this news item).
     *
     * @param stdClass $user         the user to evaluate (with ->id set)
     * @param array    $restrictions the fully decoded restrictions array
     * @return bool
     */
    abstract public function user_matches(stdClass $user, array $restrictions): bool;
}
