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
 * Shortcodes for local_wb_news
 *
 * @package local_wb_news
 * @subpackage db
 * @since Moodle 3.11
 * @copyright 2024 Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_wb_news;

use local_wb_news\output\wb_news;


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/wb_news/lib.php');

/**
 * Deals with local_shortcodes regarding booking.
 */
class shortcodes {

    /**
     * Prints out list of previous history items in a card..
     * Arguments can be 'userid'.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews($shortcode, $args, $content, $env, $next) {

        global $USER, $PAGE, $OUTPUT;

        // If the id argument was not passed on, we have a fallback in the connfig.

        if (!isset($args['instance'])) {
            $instance = 0;
        } else {
            $instance = (int)$args['instance'];
        }

        $news = new wb_news($instance);

        $data = $news->return_list();
        if (empty($data["instances"][0]["news"])) {
            $out = get_string('novalidinstance', 'local_wb_news', $instance);
        } else {
            $out = $OUTPUT->render_from_template('local_wb_news/wb_news_container', $data);
        }

        return $out;
    }
}
