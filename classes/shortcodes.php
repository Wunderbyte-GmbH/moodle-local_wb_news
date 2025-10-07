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
use local_wb_news\helper;
use stdClass;
use core_course\external\course_summary_exporter;
use Throwable;

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


    /**
     * use wbnews to get courselist
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_course($shortcode, $args, $content, $env, $next) {
        global $USER, $PAGE, $OUTPUT, $CFG;
        require_once($CFG->dirroot . '/course/externallib.php');

        $data = new stdClass();
        if (!isset($args['ids'])) {
            return 'please enter ids';
        } else {
            $courseids = array_map('trim', explode(",", $args['ids']));
        }
        $params = ['ids' => $courseids];
        $courses = [];
        $renderer = $PAGE->get_renderer(component: 'core');
        $warnings = [];

        foreach ($courseids as $id) {
            try {
                $courses[] = get_course($id);
            } catch (Throwable $e) {
                $warnings[] = "course not found: $id";
                continue;
            }
        }
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);
            $exporter = new course_summary_exporter($course, ['context' => $context, 'isfavourite' => false]);

            $formattedcourse = $exporter->export($renderer);
            $formattedcourse->courseimage = helper::get_course_image($course);

            $formattedcourses[] = $formattedcourse;
            if (!$formattedcourse->courseimage) {
                $formattedcourse->courseimage = "https://placehold.co/600x400";
            }
        }

        $chunks = array_chunk($formattedcourses, 3);
        $templatecontext['chunks'] = [];

        foreach ($chunks as $index => $chunk) {
            $templatecontext['chunks'][] = [
                'courses' => $chunk,
                'first' => ($index === 0),
                'index' => $index,
            ];
        }

        if (!isset($args['template'])) {
            $template = 'courselist';
        } else {
            $template = $args['template'];
        }
        $out = $OUTPUT->render_from_template('local_wb_news/courses/' . $template, $templatecontext);
        return $out . implode("<br>", $warnings);
    }

    /**
     * use wbnews to get courselist
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_mycourses($shortcode, $args, $content, $env, $next) {
        global $USER, $PAGE, $OUTPUT, $CFG;

        require_once($CFG->dirroot . '/course/externallib.php');
        require_once($CFG->dirroot . '/blocks/mycourses/classes/output/inprogress_view.php');
        require_once($CFG->dirroot . '/blocks/mycourses/locallib.php');

        $mycompletion = mycourses_get_my_completion();

        $availableview = new \block_mycourses\output\inprogress_view($mycompletion);
        $templatecontext = $availableview->export_for_template($OUTPUT);
        if (empty($templatecontext['courses'])) {
            return '';
        }
        return $OUTPUT->render_from_template('local_wb_news/block_mycourses/inprogress-view', $templatecontext);
    }

    /**
     * use wbnews to get courselist
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_availablecourses($shortcode, $args, $content, $env, $next) {
        global $USER, $PAGE, $OUTPUT, $CFG;
        return '';
        require_once($CFG->dirroot . '/course/externallib.php');
        require_once($CFG->dirroot . '/blocks/mycourses/classes/output/available_view.php');
        require_once($CFG->dirroot . '/blocks/mycourses/locallib.php');

        $mycompletion = mycourses_get_my_completion();

        $availableview = new \block_mycourses\output\available_view($mycompletion);
        $templatecontext = $availableview->export_for_template($OUTPUT);
        if (empty($templatecontext['courses'])) {
            return '';
        }
        return $OUTPUT->render_from_template('local_wb_news/block_mycourses/available-view', $templatecontext);
    }

    /**
     * use wbnews to get courselist
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_inprogresscourses($shortcode, $args, $content, $env, $next) {
        global $USER, $PAGE, $OUTPUT, $CFG;
        require_once($CFG->dirroot . '/course/externallib.php');
        require_once($CFG->dirroot . '/blocks/mycourses/classes/output/inprogress_view.php');
        require_once($CFG->dirroot . '/blocks/mycourses/locallib.php');

        $mycompletion = mycourses_get_my_completion();

        $availableview = new \block_mycourses\output\inprogress_view($mycompletion);
        $templatecontext = $availableview->export_for_template($OUTPUT);
        if (empty($templatecontext['courses'])) {
            return '';
        }
        return $OUTPUT->render_from_template('local_wb_news/block_mycourses/inprogress-view', $templatecontext);
    }

    /**
     * use wbnews to get courselist
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_completedcourses($shortcode, $args, $content, $env, $next) {
        global $USER, $PAGE, $OUTPUT, $CFG;
        require_once($CFG->dirroot . '/course/externallib.php');
        require_once($CFG->dirroot . '/blocks/mycourses/classes/output/completed_view.php');
        require_once($CFG->dirroot . '/blocks/mycourses/locallib.php');

        $mycompletion = mycourses_get_my_archive();
        $mycompletion = mycourses_get_my_archive();

        $availableview = new \block_mycourses\output\completed_view($mycompletion);
        $formattedcourses = $availableview->export_for_template($OUTPUT);

        if (empty($formattedcourses)) {
            return '';
        }
        $chunks = array_chunk($formattedcourses['courses'], 3);
        $templatecontext['chunks'] = [];
        foreach ($chunks as $index => $chunk) {
            $templatecontext['chunks'][] = [
                'courses' => $chunk,
                'first' => ($index === 0),
                'index' => $index,
            ];
        }

        return $OUTPUT->render_from_template('local_wb_news/block_mycourses/slider', $templatecontext);
    }

    /**
     * Output last visited courses
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_lastvisitedcourses($shortcode, $args, $content, $env, $next) {
        global $USER, $DB, $OUTPUT, $PAGE;
    
        if (isguestuser() || !isloggedin()) {
            return '';
        }
    
        $limit = isset($args['limit']) ? (int)$args['limit'] : 3;
        $renderer = $PAGE->get_renderer('core');
    
        // Fetch last visited unique courses
        $sql = "SELECT DISTINCT l.courseid, MAX(l.timecreated) AS lastaccess
                  FROM {logstore_standard_log} l
                 WHERE l.userid = :userid
                   AND l.courseid != 1
                   AND l.eventname = :eventname
              GROUP BY l.courseid
              ORDER BY lastaccess DESC";
        $params = [
            'userid' => $USER->id,
            'eventname' => '\\core\\event\\course_viewed'
        ];
    
        $logrecords = $DB->get_records_sql($sql, $params, 0, $limit);
        if (!$logrecords) {
            return 'Keine kürzlich besuchten Kurse gefunden.';
        }
    
        $coursesdata = [];
        foreach ($logrecords as $log) {
            if (!$DB->record_exists('course', ['id' => $log->courseid])) {
                continue;
            }
            $course = get_course($log->courseid);
            $context = \context_course::instance($course->id);
            $exporter = new course_summary_exporter($course, ['context' => $context, 'isfavourite' => false]);
            $formatted = $exporter->export($renderer);
            $formatted->courseimage = helper::get_course_image($course);
    
            if (empty($formatted->courseimage)) {
                $formatted->courseimage = "https://placehold.co/600x400";
            }
    
            $coursesdata[] = [
                'id' => $course->id,
                'fullname' => $formatted->fullname,
                'summary' => $formatted->summary,
                'image' => $formatted->courseimage,
                'url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out()
            ];
        }
    
        // Pass data to Mustache template
        $data = ['courses' => $coursesdata];
    
        // Render output
        return $OUTPUT->render_from_template('local_wb_news/block_mycourses/lastvisited-view', $data);
    }

    /**
     * Render HLFS news list and weather slider in a Moodle-compatible Mustache template.
     *
     * @param string $shortcode
     * @param array $args
     * @param string|null $content
     * @param object $env
     * @param Closure $next
     * @return string
     */
    public static function wbnews_hlfs_news($shortcode, $args, $content, $env, $next) {
        global $OUTPUT;

        $cache = \cache::make('local_wb_news', 'hlfsnews');
        $newsitems = $cache->get('latestnews');
        $weatheritems = $cache->get('weather');

        if ($newsitems === false || $weatheritems === false) {
            $newsitems = [];
            $weatheritems = [];

            $url = "https://hlfs.hessen.de/";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
            $html = curl_exec($ch);
            curl_close($ch);

            if ($html) {
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $dom->loadHTML($html);
                libxml_clear_errors();
                $xpath = new \DOMXPath($dom);

                // Scrape news
                $articles = $xpath->query("//article[contains(@class, 'media-teaser__list-wrapper')]//article[contains(@class, 'hw-article')]");
                foreach ($articles as $article) {
                    $atag = $xpath->query(".//a[contains(@class, 'teaser--area-link')]", $article)->item(0);
                    $link = $atag ? 'https://hlfs.hessen.de' . $atag->getAttribute('href') : '';

                    $datenode = $xpath->query(".//p[contains(@class, 'date')]", $article)->item(0);
                    $date = $datenode && $datenode->textContent ? trim($datenode->textContent) : '';

                    $categorynode = $xpath->query(".//p[contains(@class, 'teaser-generic__field-pt-dateline')]", $article)->item(0);
                    $category = $categorynode && $categorynode->textContent ? trim($categorynode->textContent) : '';

                    $titlenode = $xpath->query(".//h2[contains(@class, 'headline')]", $article)->item(0);
                    $title = $titlenode && $titlenode->textContent ? trim($titlenode->textContent) : '';

                    $teasernode = $xpath->query(".//div[contains(@class, 'teasertext')]", $article)->item(0);
                    $teaser = $teasernode && $teasernode->textContent ? trim($teasernode->textContent) : '';

                    if ($title && $link) {
                        $newsitems[] = [
                            'date' => $date,
                            'category' => $category,
                            'title' => $title,
                            'teaser' => $teaser,
                            'link' => $link
                        ];
                    }
                }

                // Scrape weather
                $carousel_items = $xpath->query("//div[@id='weather-slider']//div[contains(@class, 'carousel-item')]");
                foreach ($carousel_items as $item) {
                    $city_node = $xpath->query(".//span[contains(@class, 'title')]", $item)->item(0);
                    $temp_node = $xpath->query(".//span[contains(@class, 'temp')]", $item)->item(0);
                    $img_node = $xpath->query(".//img", $item)->item(0);

                    $city = $city_node ? trim($city_node->textContent) : '';
                    $temp = $temp_node ? trim($temp_node->textContent) : '';
                    $icon = $img_node ? $img_node->getAttribute('src') : '';
                    $icon = $icon ? 'https://hlfs.hessen.de' . $icon : '';

                    if ($city && $temp) {
                        $weatheritems[] = [
                            'city' => $city,
                            'temp' => $temp,
                            'icon' => $icon
                        ];
                    }
                }

                // Cache results
                $cache->set('latestnews', $newsitems);
                $cache->set('weather', $weatheritems);
            }
        }

        $templatecontext = [
            'news' => $newsitems,
            'weather' => $weatheritems
        ];

        return $OUTPUT->render_from_template('local_wb_news/extnews/externnews', $templatecontext);
    }

}
