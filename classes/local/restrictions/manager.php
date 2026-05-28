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
 * Restriction manager – discovers and delegates to all restriction handlers.
 *
 * @package   local_wb_news
 * @copyright 2024 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wb_news\local\restrictions;

use MoodleQuickForm;
use stdClass;

/**
 * Discovers all restriction handlers in this directory and orchestrates them.
 *
 * Adding a new restriction type only requires placing a class that extends
 * {@see base} in this directory. The manager will find it automatically.
 */
class manager {
    /** @var base[]|null cached handler instances, keyed by basename */
    private static ?array $handlers = null;

    /**
     * Returns all discovered restriction handler instances.
     *
     * The directory is scanned once per request; results are cached statically.
     *
     * @return base[]
     */
    public static function get_handlers(): array {
        if (self::$handlers !== null) {
            return self::$handlers;
        }

        global $CFG;
        self::$handlers = [];

        $dir = $CFG->dirroot . '/local/wb_news/classes/local/restrictions';
        foreach (glob($dir . '/*.php') as $file) {
            $basename = basename($file, '.php');
            if (in_array($basename, ['base', 'manager'], true)) {
                continue;
            }

            // Ensure the file is loaded (Moodle autoloader covers PSR-4 classes,
            // but explicit require_once guarantees freshly added files are found).
            require_once($file);

            $classname = 'local_wb_news\\local\\restrictions\\' . $basename;
            if (class_exists($classname) && is_subclass_of($classname, base::class)) {
                self::$handlers[$basename] = new $classname();
            }
        }

        return self::$handlers;
    }

    /**
     * Decode a restrictions JSON string into an associative array.
     *
     * @param string|null $json
     * @return array
     */
    public static function decode_restrictions(?string $json): array {
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Add all restriction form fields after the Restrictions mform header.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    public static function add_form_fields(MoodleQuickForm $mform): void {
        foreach (self::get_handlers() as $handler) {
            $handler->add_form_fields($mform);
        }
    }

    /**
     * Populate form defaults for all restriction types from the stored JSON.
     *
     * @param stdClass $data         form data object (modified in-place)
     * @param array    $restrictions decoded restrictions array
     * @return void
     */
    public static function set_form_defaults(stdClass $data, array $restrictions): void {
        foreach (self::get_handlers() as $handler) {
            $handler->set_form_defaults($data, $restrictions);
        }
    }

    /**
     * Build and return the restrictions JSON string from submitted form data.
     *
     * Each handler contributes its slice; the results are merged into one JSON
     * object. Returns null when no handler produces any restriction data.
     *
     * @param stdClass $data submitted form data
     * @return string|null
     */
    public static function build_restrictions_json(stdClass $data): ?string {
        $result = [];
        foreach (self::get_handlers() as $handler) {
            $part = $handler->process_form_data($data);
            if (!empty($part)) {
                $result = array_merge($result, $part);
            }
        }
        return empty($result) ? null : json_encode($result);
    }

    /**
     * Return true when $user passes ALL active restrictions for $newsitem.
     *
     * @param stdClass $newsitem  news record (with ->restrictions field)
     * @param stdClass $user      user record (with ->id field)
     * @return bool
     */
    public static function user_matches(stdClass $newsitem, stdClass $user): bool {
        $restrictions = self::decode_restrictions($newsitem->restrictions ?? null);

        if (empty($restrictions)) {
            return true;
        }

        foreach (self::get_handlers() as $handler) {
            if (!$handler->user_matches($user, $restrictions)) {
                return false;
            }
        }

        return true;
    }
}
