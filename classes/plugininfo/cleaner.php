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
 * @package    local_datacleaner
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacleaner\plugininfo;

use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();

class cleaner extends base {

    /**
     * Whether the subplugin is enabled.
     *
     * @return bool Whether enabled.
     */
    public function enabled() {
        return get_config('cleaner_' . $this->name, 'enabled');
    }

    /**
     * Get a list of enabled plugins.
     */
    static function get_enabled_plugins() {
        global $DB;
        $where = $DB->sql_compare_text('plugin') . " LIKE ? AND " . $DB->sql_compare_text('name') . " = ? AND value = ? ";
        $params = array('cleaner_%', 'enabled', 1);
        $results = $DB->get_records_select_menu('config_plugins', $where, $params, 'plugin ASC', 'plugin, plugin AS val');
        // Strip 'cleaner_' from the front
        $final = array();
        foreach ($results as $result) {
            $key = substr($result, 8);
            $final[$key] = $key;
        }
        return $final;
    }

    /**
     * Get enabled plugins, sorted by priority
     *
     * @return array Enabled plugins, sorted by priority
     */
    static public function get_enabled_plugins_by_priority()
    {
        $fileinfo = \core_plugin_manager::instance()->get_present_plugins('cleaner');
        $versions = \core_plugin_manager::instance()->get_plugins_of_type('cleaner');
        $enabled = self::get_enabled_plugins();

        $grouped = array();
        foreach ($enabled as $one) {
            $priority = $fileinfo[$one]->priority;
            $groups[$priority][] = $versions[$one];
        }

        // Sort
        sort($groups, SORT_NUMERIC);

        // Flatten
        $final = array();
        foreach ($groups as $group) {
            $final = array_merge($final, $group);
        }

        return $final;
    }

    /**
     * Yes you can uninstall these plugins if you want.
     * @return \moodle_url
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Return URL used for management of plugins of this type.
     * @return \moodle_url
     */
    public static function get_manage_url() {
        return new \moodle_url('/admin/settings.php', array('section' => 'local_cleaner'));
    }

    /**
     * Include the settings.php file from sub plugins if they provide it.
     * This is a copy of very similar implementations from various other subplugin areas.
     *
     * @return \moodle_url
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Get the settings section name.
     * It's used to get the setting links in the cleaner sub-plugins table.
     *
     * @return null|string the settings section name.
     */
    public function get_settings_section_name() {
        if (file_exists($this->full_path('settings.php'))) {
            return 'cleaner_' . $this->name;
        }
        else {
            return null;
        }
    }

    /**
     * Get the settings section url.
     *
     * @return null|string the settings section name.
     */
    public function get_settings_section_url() {
        if (file_exists($this->full_path('settings.php'))) {
            return new \moodle_url('/admin/settings.php', array('section' => $this->get_settings_section_name()));
        }
        else {
            return null;
        }
    }
}

