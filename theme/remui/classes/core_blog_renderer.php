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
 * Core blog renderer
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Blog renderer
 */
class theme_remui_core_blog_renderer extends plugin_renderer_base {

    /**
     * Renders a blog entry
     *
     * @param blog_entry $entry
     * @return string The table HTML
     */
    public function render_blog_entry(blog_entry $entry) {
        $entryid = optional_param('entryid', 'none', PARAM_INT);
        if ($entryid == 'none') {
            $ou = $this->render_blog_archive($entry);
        } else {
            $ou = $this->render_individual_blog($entry);
        }
        return $ou;
    }

    /**
     * Renders an entry attachment
     *
     * Print link for non-images and returns images as HTML
     *
     * @param blog_entry_attachment $attachment
     * @return string List of attachments depending on the $return input
     */
    public function blog_entry_attachment_image(blog_entry_attachment $attachment) {

        // Image attachments don't get printed as links.
        if (file_mimetype_in_typegroup($attachment->file->get_mimetype(), 'web_image')) {
            $attrs = array('src' => $attachment->url, 'alt' => '');
            return $attrs;
        }
    }

    public function render_blog_archive(blog_entry $entry) {
        global $OUTPUT;
        $syscontext = context_system::instance();

        // Header.
        $mainclass = 'forumpost blog_entry blog clearfix ';
        if ($entry->renderable->unassociatedentry) {
            $mainclass .= 'draft';
        } else {
            $mainclass .= $entry->publishstate;
        }

        // Determine text for publish state.
        switch ($entry->publishstate) {
            case 'draft':
                $blogtype = get_string('publishtonoone', 'blog');
                break;
            case 'site':
                $blogtype = get_string('publishtosite', 'blog');
                break;
            case 'public':
                $blogtype = get_string('publishtoworld', 'blog');
                break;
            default:
                $blogtype = '';
                break;

        }
        // $ou .= $this->output->container($blogtype, 'audience');

        // Attachments.
        $imageurl = "";
        $imagealt = "";
        if ($entry->renderable->attachments) {
            foreach ($entry->renderable->attachments as $attachment) {
                $imageattr = $this->blog_entry_attachment_image($attachment);
                if (is_array($imageattr)) {
                    $imageurl = $imageattr["src"];
                    $imagealt = $imageattr["alt"];
                }
            }
        }
        if (!$imageurl) {
            $imageurl  = $OUTPUT->pix_url('400x300', 'theme');
        }
        // Title.
        $titlelink = html_writer::link(new moodle_url('/blog/index.php',
                                                       array('entryid' => $entry->id)),
                                                       format_string($entry->subject));

        $ou = html_writer::start_div('container-fluid', array('id' => 'blogpost-container'));
            $ou .= html_writer::start_div('row');
                // Blog image.
                $ou .= html_writer::start_div('col-lg-3 col-sm-12', array('id' => 'blogpost-image',
                    'style' => 'background-image: url(' . $imageurl . ');
                    background-size: cover;
                    background-position: center'));
                    // $ou .= html_writer::img($imageurl, $imagealt);
                $ou .= html_writer::end_div();
                // Blog name and description.
                $ou .= html_writer::start_div('col-lg-9 col-sm-12', array('id' => 'blogpost-description', 'class' => 'pad-20'));
                     $ou .= html_writer::tag('h3', $titlelink, array('class' => 'heading'));

                    // Post by.
                    $by = new stdClass();
                    $fullname = fullname($entry->renderable->user, has_capability('moodle/site:viewfullnames', $syscontext));
                    $userurlparams = array('id' => $entry->renderable->user->id, 'course' => $this->page->course->id);
                    $by->name = html_writer::link(new moodle_url('/user/view.php', $userurlparams), $fullname);

                    $by->date = userdate($entry->created);
                    $ou .= $this->output->container(get_string('bynameondate', 'forum', $by), 'author text-muted');
                    $description = strip_tags(format_text($entry->summary, $entry->summaryformat,
                        array('overflowdiv' => true)));

        if (strlen($description) > 200) {
            $description = substr($description, 0, 200) . "..";
        }
                    $ou .= html_writer::div($description, "blog-description");
                    $titlelink = new moodle_url('/blog/index.php', array('entryid' => $entry->id));
                    $ou .= html_writer::link($titlelink, get_string('viewblog', 'theme_remui'), array("class" => "continue-reading btn"));

                $ou .= html_writer::end_div();
            $ou .= html_writer::end_div();
        $ou .= html_writer::end_div();
        return $ou;
    }

    public function render_individual_blog(blog_entry $entry) {
                global $CFG;

        $syscontext = context_system::instance();

        $stredit = get_string('edit');
        $strdelete = get_string('delete');

        // Header.
        $mainclass = 'forumpost blog_entry blog clearfix ';
        if ($entry->renderable->unassociatedentry) {
            $mainclass .= 'draft';
        } else {
            $mainclass .= $entry->publishstate;
        }
        $ou = $this->output->container_start($mainclass, 'b' . $entry->id);

        // Post content.
        $ou .= $this->output->container_start('row maincontent clearfix');

        // Entry.
        $ou .= $this->output->container_start('no-overflow content ');

        // Determine text for publish state.
        switch ($entry->publishstate) {
            case 'draft':
                $blogtype = get_string('publishtonoone', 'blog');
                break;
            case 'site':
                $blogtype = get_string('publishtosite', 'blog');
                break;
            case 'public':
                $blogtype = get_string('publishtoworld', 'blog');
                break;
            default:
                $blogtype = '';
                break;

        }

        $ou .= $this->output->container($blogtype, 'audience');

        // Attachments.
        // $attachmentsoutputs = array();
        if ($entry->renderable->attachments) {
            foreach ($entry->renderable->attachments as $attachment) {
                $ou .= $this->render($attachment, false);
            }
        }

        // Title.
        $titlelink = html_writer::link(new moodle_url('/blog/index.php',
                                                       array('entryid' => $entry->id)),
                                                       format_string($entry->subject));
        $ou .= html_writer::tag('h3', $titlelink, array('class' => 'heading'));

        // Body.
        $ou .= format_text($entry->summary, $entry->summaryformat, array('overflowdiv' => true));

        if (!empty($entry->uniquehash)) {
            // Uniquehash is used as a link to an external blog.
            $url = clean_param($entry->uniquehash, PARAM_URL);
            if (!empty($url)) {
                $ou .= $this->output->container_start('externalblog');
                $ou .= html_writer::link($url, get_string('linktooriginalentry', 'blog'));
                $ou .= $this->output->container_end();
            }
        }

        // $ou  .= html_writer::tag('div', array('class' => 'clearfix'));
        // Stasrt of Header Tag with date and author
        // $ou .= $this->output->container_start('row clearfix');

        // User picture.
        // $ou .= $this->output->container_start('left picture header');
        // $ou .= $this->output->user_picture($entry->renderable->user);
        // $ou .= $this->output->container_end();

        $ou .= $this->output->container_start('header');

        // Post by.
        $by = new stdClass();
        $fullname = fullname($entry->renderable->user, has_capability('moodle/site:viewfullnames', $syscontext));
        $userurlparams = array('id' => $entry->renderable->user->id, 'course' => $this->page->course->id);
        $by->name = html_writer::link(new moodle_url('/user/view.php', $userurlparams), $fullname);

        $by->date = userdate($entry->created);
        $ou .= $this->output->container(get_string('bynameondate', 'forum', $by), 'author text-muted');

        // Adding external blog link.
        if (!empty($entry->renderable->externalblogtext)) {
            $ou .= $this->output->container($entry->renderable->externalblogtext, 'externalblog');
        }

        // Closing subject tag and header tag.
        $ou .= $this->output->container_end();
        // $ou .= $this->output->container_end();
        // Links to tags.
        // $oufficialtags = tag_get_tags_csv('post', $entry->id, TAG_RETURN_HTML, 'official');
        if ($CFG->version / 1000000 < 2016 ) {
            $oufficialtags = tag_get_tags_csv('post', $entry->id, TAG_RETURN_HTML, 'official');
            $defaulttags = tag_get_tags_csv('post', $entry->id, TAG_RETURN_HTML, 'default');
        } else {
            $oufficialtags = core_tag_tag::get_item_tags_array('post', $entry->id, TAG_RETURN_HTML, 'official');
            $defaulttags = core_tag_tag::get_item_tags_array('post', $entry->id, TAG_RETURN_HTML, 'default');
        }
        if (!empty($CFG->usetags) && ($oufficialtags || $defaulttags) ) {
            $ou .= $this->output->container_start('tags');

            if ($oufficialtags) {
                $ou .= get_string('tags', 'tag') .': '. $this->output->container($oufficialtags, 'officialblogtags');
                if ($defaulttags) {
                    $ou .= ', ';
                }
            }
            $ou .= $defaulttags;
            $ou .= $this->output->container_end();
        }

        // Add associations.
        if (!empty($CFG->useblogassociations) && !empty($entry->renderable->blogassociations)) {

            // First find and show the associated course.
            $assocstr = '';
            $coursesarray = array();
            foreach ($entry->renderable->blogassociations as $assocrec) {
                if ($assocrec->contextlevel == CONTEXT_COURSE) {
                    $coursesarray[] = $this->output->action_icon($assocrec->url, $assocrec->icon, null, array(), true);
                }
            }
            if (!empty($coursesarray)) {
                $assocstr .= get_string('associated', 'blog', get_string('course')) . ': ' . implode(', ', $coursesarray);
            }

            // Now show mod association.
            $modulesarray = array();
            foreach ($entry->renderable->blogassociations as $assocrec) {
                if ($assocrec->contextlevel == CONTEXT_MODULE) {
                    $str = get_string('associated', 'blog', $assocrec->type) . ': ';
                    $str .= $this->output->action_icon($assocrec->url, $assocrec->icon, null, array(), true);
                    $modulesarray[] = $str;
                }
            }
            if (!empty($modulesarray)) {
                if (!empty($coursesarray)) {
                    $assocstr .= '<br/>';
                }
                $assocstr .= implode('<br/>', $modulesarray);
            }

            // Adding the asociations to the output.
            $ou .= $this->output->container($assocstr, 'tags');
        }

        if ($entry->renderable->unassociatedentry) {
            $ou .= $this->output->container(get_string('associationunviewable', 'blog'), 'noticebox');
        }

        // Commands.
        $ou .= $this->output->container_start('commands');
        if ($entry->renderable->usercanedit) {

            // External blog entries should not be edited.
            if (empty($entry->uniquehash)) {
                $ou .= html_writer::link(new moodle_url('/blog/edit.php',
                                                        array('action' => 'edit', 'entryid' => $entry->id)),
                                                        $stredit) . ' ';
            }
            $ou .= html_writer::link(new moodle_url('/blog/edit.php',
                                                    array('action' => 'delete', 'entryid' => $entry->id)),
                                                    $strdelete) . ' ';
        }

        $entryurl = new moodle_url('/blog/index.php', array('entryid' => $entry->id));
        $ou .= html_writer::link($entryurl, get_string('permalink', 'blog'));

        $ou .= $this->output->container_end();

        // Last modification.
        if ($entry->created != $entry->lastmodified) {
            $ou .= $this->output->container(' [ '.get_string('modified').': '.userdate($entry->lastmodified).' ]');
        }

        // Comments.
        if (!empty($entry->renderable->comment)) {
            $ou .= $entry->renderable->comment->output(true);
        }

        $ou .= $this->output->container_end();

        // Closing maincontent div.
        $ou .= $this->output->container('&nbsp;', 'side options');
        $ou .= $this->output->container_end();

        $ou .= $this->output->container_end();

        return $ou;
    }

    public function render_blog_entry_attachment(blog_entry_attachment $attachment) {

        $syscontext = context_system::instance();

        // Image attachments don't get printed as links.
        if (file_mimetype_in_typegroup($attachment->file->get_mimetype(), 'web_image')) {
            $attrs = array('src' => $attachment->url, 'alt' => '');
            $ou = html_writer::empty_tag('img', $attrs);
            $class = 'attachedimages';
        } else {
            $image = $this->output->pix_icon(file_file_icon($attachment->file),
                                             $attachment->filename,
                                             'moodle',
                                             array('class' => 'icon'));
            $ou = html_writer::link($attachment->url, $image);
            $ou .= format_text(html_writer::link($attachment->url, $attachment->filename),
                              FORMAT_HTML,
                              array('context' => $syscontext));
            $class = 'attachments';
        }

        return $this->output->container($ou, $class);
    }
}
