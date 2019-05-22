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
 * @author Mark Webster <mark.webster@catalyst-eu.net>
 * @author Brian Barnes <brian.barnes@shezarlms.com>
 * @package shezar
 * @subpackage theme
 */

if (!empty($PAGE->theme->settings->logo)) {
    $logourl = $PAGE->theme->setting_file_url('logo', 'logo');
    $logoalt = get_string('logo', 'theme_standardshezarresponsive', $SITE->fullname);
} else {
    $logourl = $OUTPUT->pix_url('logo', 'theme');
    $logoalt = get_string('shezarlogo', 'theme_standardshezarresponsive');
}

if (!empty($PAGE->theme->settings->alttext)) {
    $logoalt = format_string($PAGE->theme->settings->alttext);
}

if (!empty($PAGE->theme->settings->favicon)) {
    $faviconurl = $PAGE->theme->setting_file_url('favicon', 'favicon');
} else {
    $faviconurl = $OUTPUT->favicon();
}

$haslogininfo = empty($PAGE->layout_options['nologininfo']);
$haslangmenu = (!isset($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu'] );

$hasshezarmenu = false;
$shezarmenu = '';
if (empty($PAGE->layout_options['nocustommenu'])) {
    // load shezar menu
    $menudata = shezar_build_menu();
    $shezar_core_renderer = $PAGE->get_renderer('shezar_core');
    $shezarmenu = $shezar_core_renderer->shezar_menu($menudata);
    $hasshezarmenu = !empty($shezarmenu);
}

$regionmain = 'span9 pull-right';
$sidepre = 'span3 desktop-first-column';
$left = true;
// Reset layout mark-up for RTL languages.
if (right_to_left()) {
    $regionmain = 'span9';
    $sidepre = 'span3 pull-right';
    $left = false;
}

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $faviconurl; ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes('two-column'); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<header role="banner" class="navbar">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <?php if ($logourl == NULL) { ?>
                <div id="logo" class="img-responsive"><a href="<?php echo $CFG->wwwroot; ?>">&nbsp;</a></div>
            <?php } else { ?>
                <div id="logo" class="custom img-responsive">
                    <a href="<?php echo $CFG->wwwroot; ?>">
                        <img class="logo img-responsive" src="<?php echo $logourl;?>" alt="<?php echo $logoalt; ?>" />
                    </a>
                </div>
            <?php } ?>
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse" href='#'>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="accesshide"><?php echo get_string('togglenavigation', 'core'); ?></span>
            </a>
            <?php echo ($haslangmenu && (!isloggedin() || isguestuser()) ? $OUTPUT->lang_menu() : '') . $OUTPUT->user_menu() ?>
            <?php echo $OUTPUT->page_heading(); ?>
            <?php if ($hasshezarmenu) { ?>
                <div id="shezarmenu" class="nav-collapse collapse"><?php echo $shezarmenu; ?></div>
            <?php } ?>
        </div>
    </nav>
</header>

<div id="page" class="container-fluid">
    <?php echo $OUTPUT->full_header(); ?>

    <div id="page-content" class="row-fluid">
        <section id="region-main" class="<?php echo $regionmain ?>">
            <?php
            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </section>
        <?php echo $OUTPUT->blocks('side-pre', $sidepre); ?>
    </div>

</div>

<footer id="page-footer">
    <div class="container-fluid">
        <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
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

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</body>
</html>
