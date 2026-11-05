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
 * Admin settings for local_wb_news.
 *
 * @package   local_wb_news
 * @copyright 2026 Wunderbyte GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_wb_news', get_string('pluginname', 'local_wb_news'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox(
            'local_wb_news/notificationsenabled',
            get_string('notificationsenabled', 'local_wb_news'),
            get_string('notificationsenabled_desc', 'local_wb_news'),
            0
        ));

        $settings->add(new admin_setting_configmultiselect(
            'local_wb_news/notificationinstances',
            get_string('notificationinstances', 'local_wb_news'),
            get_string('notificationinstances_desc', 'local_wb_news'),
            [],
            \local_wb_news\news::get_instance_options()
        ));
    }
}
