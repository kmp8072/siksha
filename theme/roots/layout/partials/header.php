<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright 2016 onwards shezar Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   theme_roots
 * @author    Joby Harding <joby.harding@shezarlearning.com>
 */

defined('MOODLE_INTERNAL') || die();

global $OUTPUT;
?>
<nav role="navigation" class="navbar navbar-default navbar-site
">
    <div class="container-fluid">

        <div class="navbar-header pull-left">
            <?php echo $themerenderer->render(new theme_roots\output\site_logo()); ?>
        </div>

        <div class="navbar-header pull-right">
            <?php
                if ($hasshezarmenu) {
                    echo $OUTPUT->navbar_button();
                }

                // Add profile menu (for logged in) or language menu (not logged in).
                $haslangmenu = (!isset($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu'] );
                echo ($haslangmenu && (!isloggedin() || isguestuser()) ? $OUTPUT->lang_menu() : '') . $OUTPUT->user_menu();
            ?>
        </div>

    </div>
    <?php if ($hasshezarmenu) { echo $shezarmenu; } ?>
</nav>
