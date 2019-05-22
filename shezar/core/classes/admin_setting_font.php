<?php
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * shezar core font admin setting.
 *
 * This is a copy of the admin setting from mod/certificate.
 * We can't use that one here because it would create an underlying dependency, but we can very easily create
 * a copy that is now usable by all shezar components.
 *
 * @package   shezar core
 * @copyright 2015 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@shezarlms.com>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/adminlib.php');

/**
 * shezar core font admin setting
 *
 * @since 2.8
 * @copyright 2015 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@shezarlms.com>
 */
class shezar_core_admin_setting_font extends admin_setting_configselect {

    /**
     * If set to true an appropriate default option will be added before all other options with a value of ''
     * When enabled the code using this setting should select an appropriate font for the given language at the time of execution.
     * @var bool
     */
    protected $useappropriatedefault = true;

    /**
     * Constructor
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string|int $defaultsetting
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $useappropriatedefault = true) {
        $this->useappropriatedefault = $useappropriatedefault;
        parent::__construct($name, $visiblename, $description, $defaultsetting, null);
    }

    /**
     * Lazy load the font options.
     *
     * @return bool true if loaded, false if error
     */
    public function load_choices() {
        global $CFG;

        if (is_array($this->choices)) {
            return true;
        }

        require_once("$CFG->libdir/pdflib.php");

        $doc = new pdf();

        $this->choices = array();
        if ($this->useappropriatedefault) {
            $this->choices[''] = get_string('fontdefault', 'shezar_core');
        }

        if (method_exists($doc, 'get_font_families')) {
            $fontfamilies = $doc->get_font_families();
            foreach ($fontfamilies as $family => $fonts) {
                $this->choices[$family] = $family;
            }
        } else {
            $this->choices['freeserif'] = 'freeserif';
            $this->choices['freesans'] = 'freesans';
        }
        return true;
    }
}