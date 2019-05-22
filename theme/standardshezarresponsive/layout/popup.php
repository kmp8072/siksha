<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
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
 * @deprecated since shezar 9
 * @author Brian Barnes <brian.barnes@shezarlms.com>
 * @package shezar
 * @subpackage theme
 */

$hasfooter = (!isset($PAGE->layout_options['nofooter']) || !$PAGE->layout_options['nofooter'] );
$hasnavbar = (!isset($PAGE->layout_options['nonavbar']) || !$PAGE->layout_options['nonavbar'] );
$left = (!right_to_left());

if (!empty($PAGE->theme->settings->favicon)) {
    $faviconurl = $PAGE->theme->setting_file_url('favicon', 'favicon');
} else {
    $faviconurl = $OUTPUT->favicon();
}

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $faviconurl; ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<?php
// If on desktop, then hide the header/footer.
$hideclass = '';
$bodynoheader = '';
$devicetype = core_useragent::get_device_type();
if ($devicetype !== 'mobile' and $devicetype !== 'tablet') {
    // We can not use the Bootstrap responsive css classes because popups are phone sized on desktop.
    $hideclass = 'hide';
    $bodynoheader = 'bodynoheader';
}
?>

<body <?php echo $OUTPUT->body_attributes(array($bodynoheader)); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<?php if ($hasnavbar) { ?>
<header role="banner" class="navbar navbar-fixed-top moodle-has-zindex <?php echo $hideclass; ?>">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <a class="brand" href="<?php echo $CFG->wwwroot;?>"><?php echo
                format_string($SITE->shortname, true, array('context' => context_course::instance(SITEID)));
                ?></a>
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse" href='#'>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="accesshide"><?php echo get_string('togglenavigation', 'core'); ?></span>
            </a>
            <div class="nav-collapse collapse">
                <ul class="nav <?php echo $left ? "pull-right" : "pull-left" ?>">
                    <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
                    <li class="navbar-text"><?php echo $OUTPUT->login_info() ?></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php } ?>

<div id="page" class="container-fluid">
    <?php echo $OUTPUT->full_header(); ?>

    <div id="page-content" class="row-fluid">
        <section id="region-main" class="span12">
            <?php
            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </section>
    </div>

    <?php if ($hasfooter) { ?>
        <footer id="page-footer">
            <div class="container-fluid">
                <?php
                if (!empty($PAGE->theme->settings->footnote)) {
                    echo '<div class="footnote text-center">'.format_text($PAGE->theme->settings->footnote).'</div>';
                }
                echo $OUTPUT->login_info();
                ?>
                <div class="footer-powered"><?php echo $OUTPUT->powered_by_shezar(); ?></div>
                <?php echo $OUTPUT->standard_footer_html(); ?>
            </div>
        </footer>
    <?php } ?>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>
