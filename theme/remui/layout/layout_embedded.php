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
 * Layout - Embedded
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

echo $OUTPUT->doctype(); ?>

<html <?php echo $OUTPUT->htmlattributes(); ?>>
    <head>
        <title><?php echo $OUTPUT->page_title(); ?></title>
        <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
        <?php echo $OUTPUT->standard_head_html(); ?>
    </head>

    <body <?php echo $OUTPUT->body_attributes(); ?>>
        <?php echo $OUTPUT->standard_top_of_body_html(); ?>
        
        <div class="remui-wrapper"> <!-- main page wrapper -->
            <div class="remui-content-wrapper">
                <!-- Main content -->
                <section class="content text-center">
                    <div id="region-main">
                        <?php echo $OUTPUT->main_content(); ?>
                            <!-- <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                            <span class="sr-only">Loading...</span> -->
                    </div>
                </section>
            </div>
        </div>
        
        <?php echo $OUTPUT->standard_end_of_body_html(); ?>
    </body>
</html>
