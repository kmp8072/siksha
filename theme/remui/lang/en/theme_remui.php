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
 * Strings for component 'theme_remui', language 'en', branch 'MOODLE_3_STABLE'
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Edwiser RemUI';
$string['region-side-post'] = 'Right';
$string['region-side-pre'] = 'Left';
$string['fullscreen'] = 'Full screen';
$string['closefullscreen'] = 'Close full screen';
$string['licensesettings'] = 'License Settings';
$string['edwiserremuilicenseactivation'] = 'Edwiser RemUI License Activation';
$string['overview'] = 'Overview';
$string['choosereadme'] = '
<div class="about-remui-wrapper" align="center">
    <div class="about-remui" style="max-width: 800px;">
        <h1 class="text-center">Welcome To Edwiser RemUI</h1><br>
        <h4 class="text-muted">
        Edwiser RemUI is the new revolution in Moodle User Experience. It has been suitably designed
        to elevate e-learning with custom layouts, simplified navigation, content creation & customization option. <br><br>
        We\'re sure you will enjoy the remodeled look!
        </h4>
        <div class="text-center">
        <img src="' . $CFG->wwwroot . '/theme/remui/pix/screenshot.jpg" alt="Edwiser RemUI screen shot" style="max-width: 100%;"/>
        </div>
        <br><br>
        <div class="text-center">
            <div class="btn-group text-center" role="group" aria-label="...">
              <div class="btn-group" role="group">
                <a href="https://edwiser.org/remui/faq/" target="_blank" class="btn btn-primary">FAQ</a>&nbsp;
              </div>
              <div class="btn-group" role="group">
                <a href="https://edwiser.org/remui/documentation/" target="_blank" class="btn btn-primary">Documentation</a>&nbsp;
              </div>
              <div class="btn-group" role="group">
                <a href="https://edwiser.org/contact-us/" target="_blank" class="btn btn-primary">Support</a>
              </div>
            </div>
        </div>
        <br>
        <h1 class="text-center">Personalize Your Theme</h1>
        <h4 class="text-muted text-center">
            We understand that not every LMS is the same. We\'ll work with you to understand your needs, and design and develop a solution to meet your goals.
        </h4>
        <br><br>
        <div class="row wdm_generalbox">
            <div class="iconbox span3 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="iconcircle">
                    <i class="fa fa-cogs"></i>
                </div>
                <div class="iconbox-content">
                    <h4>Theme Customization</h4>
                </div>
            </div>
            <div class="iconbox span3 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="iconcircle">
                    <i class="fa fa-edit"></i>
                </div>
                <div class="iconbox-content">
                    <h4>Functionality Development</h4>
                </div>
            </div>
            <br>
            <div class="iconbox span3 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="iconcircle">
                    <i class="fa fa-link"></i>
                </div>
                <div class="iconbox-content">
                    <h4>API Integration</h4>
                </div>
            </div>
            <div class="iconbox span3 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="iconcircle">
                    <i class="fa fa-life-ring"></i>
                </div>
                <div class="iconbox-content">
                    <h4>LMS Consultancy</h4>
                </div>
            </div>
        </div>
        <br>
        <br>
        <div class="text-center">
            <a class="btn btn-primary btn-lg" target="_blank" href="https://edwiser.org/contact-us/">Contact Us</a>&nbsp;&nbsp;
        </div>
    </div>
</div>
<br />';

$string['licensenotactive'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license in RemUI settings.';
$string['licensenotactiveadmin'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license <a href="'.$CFG->wwwroot.'/theme/remui/remui_license.php" >here</a>.';
$string['activatelicense'] = 'Activate License';
$string['deactivatelicense'] = 'Deactivate License';
$string['renewlicense'] = 'Renew License';
$string['active'] = 'Active';
$string['notactive'] = 'Not Active';
$string['expired'] = 'Expired';
$string['licensekey'] = 'License key';
$string['licensestatus'] = 'License Status';
$string['noresponsereceived'] = 'No response received from the server. Please try again later.';
$string['licensekeydeactivated'] = 'License Key is deactivated.';
$string['siteinactive'] = 'Site inactive (Press Activate license to activate plugin).';
$string['entervalidlicensekey'] = 'Please enter valid license key.';
$string['licensekeyisdisabled'] = 'Your license key is Disabled.';
$string['licensekeyhasexpired'] = "Your license key has Expired. Please, Renew it.";
$string['licensekeyactivated'] = "Your license key is activated.";
$string['enterlicensekey'] = "Please enter license key.";

// course
$string['nosummary'] = 'No Summary has been added in this setion of the Course.';
$string['defaultimg'] = 'Default Image 100 x 100.';
$string['choosecategory'] = 'Choose Category';
$string['allcategory'] = 'All Categories';
$string['viewcours'] = 'View Course';
$string['taught-by'] = 'Taught By';

// dashboard element -> overview
$string['enabledashboardelements'] = 'Enable Dashboard Elements';
$string['enabledashboardelementsdesc'] = 'Uncheck to disable Edwiser RemUI custom widget on dashboard.';
$string['totaldiskusage'] = 'Total Disk Usage';
$string['activemembers'] = 'Active Members';
$string['newmembers'] = 'New Members';
$string['coursesdiskusage'] = 'Courses Disk Usage';
$string['activestudents'] = 'Active Students';

// Quick meesage
$string['quickmessage'] = 'Quick Message';
$string['entermessage'] = 'Please enter some message!';
$string['selectcontact'] = 'Please select a contact!';
$string['selectacontact'] = 'Select a contact';
$string['sendmessage'] = 'Send message';
$string['yourcontactlisistempty'] = 'Your Contact list is empty!';
$string['viewallmessages'] = 'View All Messages';
$string['messagesent'] = 'Successfully sent!';
$string['messagenotsent'] = 'Message not sent! Make sure you have entered correct value.';
$string['messagenotsenterror'] = 'Message not sent! Something went wrong.';
$string['sendingmessage'] = 'Sending message...';
$string['sendmoremessage'] = 'Send more message';

// General Seetings.
$string['generalsettings' ] = 'General Settings';
$string['navsettings'] = 'Nav Settings';
$string['homepagesettings'] = 'Home Page Settings';
$string['colorsettings'] = 'Color Settings';
$string['fontsettings' ] = 'Font Settings';
$string['slidersettings'] = 'Slider Settings';
$string['configtitle'] = 'Edwiser RemUI';

// Font settings.
$string['fontselect'] = 'Font type selector';
$string['fontselectdesc'] = 'Choose from either Standard fonts or Google web fonts types. Please save to show the options for your choice.';
$string['fonttypestandard'] = 'Standard font';
$string['fonttypegoogle'] = 'Google web font';
$string['fontnameheading'] = 'Heading font';
$string['fontnameheadingdesc'] = 'Enter the exact name of the font to use for headings.';
$string['fontnamebody'] = 'Text font';
$string['fontnamebodydesc'] = 'Enter the exact name of the font to use for all other text.';

/* Dashboard Settings*/
$string['dashboardsetting'] = 'Dashboard Settings';
$string['themecolor'] = 'Theme colour';
$string['themecolordesc'] = 'What colour skin should your theme be.  This will change multiple components to produce the colour you wish across the Moodle site';
$string['themetextcolor'] = 'Text colour';
$string['themetextcolordesc'] = 'Set the colour for your text.';
$string['layout'] = 'Choose Layout';
$string['layoutdesc'] = 'Activate the layout from either Fixed Layout (header menu will be sticking to the top) or Default Layout.'; // Boxed Layout or
$string['defaultlayout'] = 'Default';
$string['fixedlayout'] = 'Fixed Header';
$string['defaultboxed'] = 'Boxed';
$string['layoutimage'] = 'Boxed Layout Background Image';
$string['layoutimagedesc'] = 'Upload the background image to be applied on Boxed Layout.';
$string['rightsidebarslide'] = 'Toggle Right Sidebar';
$string['rightsidebarslidedesc'] = 'Toggle the right sidebar by default.';
$string['leftsidebarslide'] = 'Toggle Left Sidebar';
$string['leftsidebarslidedesc'] = 'Toggle the left sidebar by default.';
$string['rightsidebarskin'] = 'Toggle Right Sidebar Skin';
$string['rightsidebarskindesc'] = 'Change the right side bar skin.';

/*color*/
$string['colorscheme'] = 'Pick a Color Scheme';
$string['colorschemedesc'] = 'You can choose a color scheme for your website from the following - Blue, Black, Purple, Green, Yellow, Blue-light, Black-light, Purple-light, Green-light & Yellow-light. <br /> <b>Light</b> - gives a light background to the left side bar.';
$string['blue'] = 'Blue';
$string['white'] = 'White';
$string['purple'] = 'Purple';
$string['green'] = 'Green';
$string['red'] = 'Red';
$string['yellow'] = 'Yellow';
$string['bluelight'] = 'Blue Light';
$string['whitelight'] = 'White Light';
$string['purplelight'] = 'Purple Light';
$string['greenlight'] = 'Green Light';
$string['redlight'] = 'Red Light';
$string['yellowlight'] = 'Yellow Light';
$string['custom'] = 'Custom';
$string['customlight'] = 'Custom Light';
$string['customskin_color'] = 'Skin Color';
$string['customskin_color_desc'] = 'You can choose custom color for your theme here.';

/* Course setting*/
$string['courseperpage'] = 'Courses Per Page';
$string['enableimgsinglecourse'] = 'Enable image on single course page';
$string['enableimgsinglecoursedesc'] = 'Uncheck to disable formating of image on single course page.';
$string['courseperpagedesc'] = 'Number of Courses to be Displayed Per Pages in Course Archive Page.';
$string['nocoursefound'] = 'No Course Found';

/*logo*/
$string['logo'] = 'Logo';
$string['logodesc'] = 'You may add the logo to be displayed on the header. Note- Preferred height is 50px. In case you wish to customise, you can do so from the custom CSS box.';
$string['siteicon'] = 'Site icon';
$string['siteicondesc'] = 'Don\'t have a logo? You could choose one from this <a href="http://fortawesome.github.io/Font-Awesome/cheatsheet/" target="_new">list</a>. <br /> Just enter the text after "fa-".';
$string['logoorsitename'] = 'Choose site logo format';
$string['logoorsitenamedesc'] = 'You can change the site header logo as per your choice. <br />The options available are: Logo - Only the logo will be shown; <br /> Sitename - Only the sitename will be shown; <br /> Icon+sitename - An icon along with the sitename will be shown.';
$string['onlylogo'] = 'Logo Only';
$string['onlysitename'] = 'Sitename Only';
$string['iconsitename'] = 'Icon and sitename';

/*favicon*/
$string['favicon'] = 'Favicon';
$string['favicondesc'] = 'Your site’s “favourite icon”. Here, you may insert the favicon for your site.';
$string['enablehomedesc'] = 'Enable Home Desc';

/*custom css*/
$string['customcss'] = 'Custom CSS';
$string['customcssdesc'] = 'You may customise the CSS from the text box above. The changes will be reflected on all the pages of your site.';

/*google analytics*/
$string['googleanalytics'] = 'Google Analytics Tracking ID';
$string['googleanalyticsdesc'] = 'Please enter your Google Analytics Tracking ID to enable analytics on your website. The  tracking ID format shold be like [UA-XXXXX-Y]';

/*theme_remUI_frontpage*/

$string['frontpageimagecontent'] = 'Header content';
$string['frontpageimagecontentdesc'] = ' This section relates to the top portion of your frontpage.';
$string['frontpageimagecontentstyle'] = 'Style';
$string['frontpageimagecontentstyledesc'] = 'You can choose between Static & Slider.';
$string['staticcontent'] = 'Static';
$string['slidercontent'] = 'Slider';
$string['addtext'] = 'Add Text';
$string['defaultaddtext'] = 'Education is a time-tested path to progress.';
$string['addtextdesc'] = 'Here you may add the text to be displayed on the front page, preferably in HTML.';
$string['uploadimage'] = 'Upload Image';
$string['uploadimagedesc'] = 'You may upload image as content for slide';
$string['video'] = 'iframe Embedded code';
$string['videodesc'] = ' Here, you may insert the iframe Embedded code of the video that is to be embedded.';
$string['contenttype'] = 'Select Content type';
$string['contentdesc'] = 'You can choose between image or give video url.';
$string['image'] = 'Image';
$string['videourl'] = 'Video URL';
$string['frontpageimge'] = '';

$string['slidercount'] = 'No of slides';
$string['slidercountdesc'] = '';
$string['one'] = '1';
$string['two'] = '2';
$string['three'] = '3';
$string['four'] = '4';
$string['five'] = '5';
$string['eight'] = '8';
$string['twelve'] = '12';

$string['slideimage'] = 'Upload images for Slider';
$string['slideimagedesc'] = 'You may upload an image as content for this slide.';
$string['slidertext'] = 'Add Slider text';
$string['defaultslidertext'] = '';
$string['slidertextdesc'] = 'You may insert the text content for this slide. Preferably in HTML.';
$string['sliderurl'] = 'Add link to Slider button';
$string['sliderbuttontext'] = 'Add Text button on slide';
$string['sliderbuttontextdesc'] = 'You may add text to the button on this slide.';
$string['sliderurldesc'] = 'You may insert the link of the page where the user will be redirected once they click on the button.';
$string['slideinterval'] = 'Slide interval';
$string['slideintervaldesc'] = 'You may set the transition time between the slides. In case if there is one slide, this option will have no effect.';
$string['sliderautoplay'] = 'Set Slider Autoplay';
$string['sliderautoplaydesc'] = 'Select ‘yes’ if you want automatic transition in your slideshow.';
$string['true'] = 'Yes';
$string['false'] = 'No';

$string['frontpageblocks'] = 'Body Content';
$string['frontpageblocksdesc'] = 'You may insert a heading for your site’s body';

$string['enablesectionbutton'] = 'Enable buttons on Sections';
$string['enablesectionbuttondesc'] = 'Enable the buttons on body sections.';

/* General section descriptions */
$string['frontpageblockiconsectiondesc'] = 'You can choose any icon from this <a href="http://fortawesome.github.io/Font-Awesome/cheatsheet/" target="_new">list</a>. Just enter the text after "fa-". ';
$string['frontpageblockdescriptionsectiondesc'] = 'A brief description about the title.';
$string['defaultdescriptionsection'] = 'Holisticly harness just in time technologies via corporate scenarios.';
$string['sectionbuttontextdesc'] = 'Enter the text for button in this Section.';
$string['sectionbuttonlinkdesc'] = 'Enter the URL link for this Section.';
$string['frontpageblocksectiondesc'] = 'Add title to this Section.';

/* block section 1 */
$string['frontpageblocksection1'] = 'Body title for 1st Section';
$string['frontpageblockdescriptionsection1'] = 'Body description for 1st Section';
$string['frontpageblockiconsection1'] = 'Font-Awesome icon for 1st Section';
$string['sectionbuttontext1'] = 'Button text for 1st Section';
$string['sectionbuttonlink1'] = 'URL link for 1st Section';


/* block section 2 */
$string['frontpageblocksection2'] = 'Body title for 2nd Section';
$string['frontpageblockdescriptionsection2'] = 'Body description for 2nd Section';
$string['frontpageblockiconsection2'] = 'Font-Awesome icon for 2nd Section';
$string['sectionbuttontext2'] = 'Button text for 2nd Section';
$string['sectionbuttonlink2'] = 'URL link for 2nd Section';


/* block section 3 */
$string['frontpageblocksection3'] = 'Body title for 3rd Section';
$string['frontpageblockdescriptionsection3'] = 'Body description for 3rd Section';
$string['frontpageblockiconsection3'] = 'Font-Awesome icon for 3rd Section';
$string['sectionbuttontext3'] = 'Button text for 3rd Section';
$string['sectionbuttonlink3'] = 'URL link for 3rd Section';


/* block section 4 */
$string['frontpageblocksection4'] = 'Body title for 4th Section';
$string['frontpageblockdescriptionsection4'] = 'Body description for 4th Section';
$string['frontpageblockiconsection4'] = 'Font-Awesome icon for 4th Section';
$string['sectionbuttontext4'] = 'Button text for 4th Section';
$string['sectionbuttonlink4'] = 'URL link for 4th Section';


// Frontpage Aboutus settings
$string['frontpageaboutus'] = 'Frontpage About us';
$string['frontpageaboutusdesc'] = 'This section is for front page About us';

$string['enablefrontpageaboutus'] = 'Enable About us section';
$string['enablefrontpageaboutusdesc'] = 'Enable the About us section in front page.';
$string['frontpageaboutusheading'] = 'About us Heading';
$string['frontpageaboutusheadingdesc'] = 'Heading for the default heading text for section';
$string['frontpageaboutustext'] = 'About us text';
$string['frontpageaboutustextdesc'] = 'Enter about us text for frontpage.';
$string['frontpageaboutusdefault'] = '<p class="lead">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
              Ut enim ad minim veniam.</p>
              <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
                  eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.Lorem ipsum dolor sit amet,
                  consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
                  labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur
                  adipisicing elit, sed do eiusmod tempor incididunt
                  ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p>';
$string['frontpageaboutusimage'] = 'Frontpage about us  Image';
$string['frontpageaboutusimagedesc'] = 'Upload the image for frontpage about us section';

// Social media settings
$string['socialmedia'] = 'Social Media Settings';
$string['socialmediadesc'] = 'Enter the social media links for your site.';
$string['facebooksetting'] = 'Facebook Settings';
$string['facebooksettingdesc'] = 'Enter your site\'s facebook page link. For eg. https://www.facebook.com/pagename';
$string['twittersetting'] = 'Twitter Settings';
$string['twittersettingdesc'] = 'Enter your site\'s twitter page link. For eg. https://www.twitter.com/pagename';
$string['linkedinsetting'] = 'Linkedin Settings';
$string['linkedinsettingdesc'] = 'Enter your site\'s linkedin page link. For eg. https://www.linkedin.com/in/pagename';
$string['gplussetting'] = 'Google Plus Settings';
$string['gplussettingdesc'] = 'Enter your site\'s Google Plus page link. For eg. https://plus.google.com/pagename';
$string['youtubesetting'] = 'YouTube Settings';
$string['youtubesettingdesc'] = 'Enter your site\'s YouTube page link. For eg. https://www.youtube.com/channel/UCU1u6QtAAPJrV0v0_c2EISA';
$string['instagramsetting'] = 'Instagram Settings';
$string['instagramsettingdesc'] = 'Enter your site\'s Instagram page link. For eg. https://www.linkedin.com/company/name';
$string['pinterestsetting'] = 'Pinterest Settings';
$string['pinterestsettingdesc'] = 'Enter your site\'s Pinterest page link. For eg. https://www.pinterest.com/name';

// Footer Section Settings
$string['footersetting'] = 'Footer Settings';
// Footer  Column 1
$string['footercolumn1heading'] = 'Footer Content for 1st Column (Left)';
$string['footercolumn1headingdesc'] = 'This section relates to the bottom portion (Column 1) of your frontpage.';

$string['footercolumn1title'] = '1st Footer Column title';
$string['footercolumn1titledesc'] = 'Add title to this column.';
$string['footercolumn1customhtml'] = 'Custom HTML';
$string['footercolumn1customhtmldesc'] = 'You can customize HTML of this column using above given textbox.';


// Footer  Column 2
$string['footercolumn2heading'] = 'Footer Content for 2nd Column (Middle)';
$string['footercolumn2headingdesc'] = 'This section relates to the bottom portion (Column 2) of your frontpage.';

$string['footercolumn2title'] = '2nd Footer Column Title';
$string['footercolumn2titledesc'] = 'Add title to this column.';
$string['footercolumn2customhtml'] = 'Custom HTML';
$string['footercolumn2customhtmldesc'] = 'You can customize HTML of this column using above given textbox.';

// Footer  Column 3
$string['footercolumn3heading'] = 'Footer Content for 3rd Column (Right)';
$string['footercolumn3headingdesc'] = 'This section relates to the bottom portion (Column 3) of your frontpage.';

$string['footercolumn3title'] = '3rd Footer Column Title';
$string['footercolumn3titledesc'] = 'Add title to this column.';
$string['footercolumn3customhtml'] = 'Custom HTML';
$string['footercolumn3customhtmldesc'] = 'You can customize HTML of this column using above given textbox.';

// Footer Bottom-Right Section
$string['footerbottomheading'] = 'Bottom Footer Setting';
$string['footerbottomdesc'] = 'Here you can specify your own link you want to enter at the bottom section of Footer';
$string['footerbottomtext'] = 'Footer Bottom-Right Text';
$string['footerbottomtextdesc'] = 'Add text to Bottom Footer Setting.';
$string['footerbottomlink'] = 'Footer Bottom-Right Link';
$string['footerbottomlinkdesc'] = 'Enter the Link for the bottom-right section of Footer. For eg. http://www.yourcompany.com';
$string['poweredbyedwiser'] = 'Powered by Edwiser';
$string['poweredbyedwiserdesc'] = 'Uncheck to remove  \'Powered by Edwiser\' from your site.';

// Login settings page code begin.

$string['loginsettings'] = 'Login Page Settings';
$string['navlogin_popup'] = 'Enable Login Popup';
$string['navlogin_popupdesc'] = 'Enable login popup in header.';
$string['loginsettingpic'] = 'Upload Background Image';
$string['loginsettingpicdesc'] = 'Upload image as a background for login form.';
$string['signuptextcolor'] = 'Signup panel text color';
$string['signuptextcolordesc'] = 'Select the text color for Signup panel.';
$string['left'] = "Left side";
$string['right'] = "Right side";

// Login settings Page code end.


// From theme snap
$string['title'] = 'Title';
$string['contents'] = 'Contents';
$string['addanewsection'] = 'Create a new section';
$string['createsection'] = 'Create section';

/* User Profile Page */

$string['blogentries'] = 'Blog Entries';
$string['discussions'] = 'Discussions';
$string['discussionreplies'] = 'Replies';
$string['aboutme'] = 'About Me';

$string['addtocontacts'] = 'Add to Contacts';
$string['removefromcontacts'] = 'Remove from Contacts';
$string['block'] = 'Block';
$string['removeblock'] = 'Remove Block';

$string['interests'] = 'Interests';
$string['institution'] = 'Institution';
$string['location'] = 'Location';
$string['description'] = 'Description';

$string['commoncourses'] = 'Common Courses';
$string['editprofile'] = 'Edit Profile';

$string['firstname'] = 'First Name';
$string['surname'] = 'Surname';
$string['email'] = 'Email';
$string['citytown'] = 'City/Town';
$string['country'] = 'Country';
$string['selectcountry'] = 'Select Country';
$string['description'] = 'Description';

$string['nocommoncourses'] = 'You have not been enrolled in any common courses with this user.';
$string['notenrolledanycourse'] = 'You have not been enrolled in any course.';
$string['usernotenrolledanycourse'] = '{$a} has not been enrolled in any course.';
$string['nobadgesyetcurrent'] = 'You have no Badges yet.';
$string['nobadgesyetother'] = 'This user has no Badges yet.';

// User profile page js

$string['actioncouldnotbeperformed'] = 'Action could not be performed!';
$string['enterfirstname'] = 'Please enter your First Name.';
$string['enterlastname'] = 'Please enter your Last Name.';
$string['enteremailid'] = 'Please enter your Email ID.';
$string['enterproperemailid'] = 'Please enter proper Email ID.';
$string['detailssavedsuccessfully'] = 'Details Saved Successfully!';

/* Header */

$string['startedsince'] = 'Started since ';
$string['startingin'] = 'Starting in ';

$string['userimage'] = 'User Image';

$string['seeallmessages'] = 'See all messages';
$string['viewallnotifications'] = 'View all notifications';
$string['viewallupcomingevents'] = 'View all upcoming events';

$string['youhavemessages'] = 'You have {$a} unread message(s)';
$string['youhavenomessages'] = 'You have no unread messages';

$string['youhavenotifications'] = 'You have {$a} notifications';
$string['youhavenonotifications'] = 'You have no notifications';

$string['youhaveupcomingevents'] = 'You have {$a} upcoming event(s)';
$string['youhavenoupcomingevents'] = 'You have no upcoming events';


/* Dashboard elements */

// Add notes
$string['addnotes'] = 'Add Notes';
$string['selectacourse'] = 'Select a Course';

$string['addsitenote'] = 'Add Site Note';
$string['addcoursenote'] = 'Add Course Note';
$string['addpersonalnote'] = 'Add Personal Note';
$string['deadlines'] = 'Deadlines';

// Add notes js
$string['selectastudent'] = 'Select a Student';
$string['total'] = 'Total';
$string['nousersenrolledincourse'] = 'There are no Users enrolled in {$a} Course.';
$string['selectcoursetodisplayusers'] = 'Select a Course to display its Enrolled users here.';


// Deadlines
$string['gotocalendar'] = 'Go to Calendar';
$string['noupcomingdeadlines'] = 'There are no upcoming deadlines!';
$string['in'] = 'In';
$string['since'] = 'Since';

// Latest Members
$string['latestmembers'] = 'Latest Members';
$string['viewallusers'] = 'View All Users';

// Recently Active Forums
$string['recentlyactiveforums'] = 'Recently Active Forums';

// Recent Assignments
$string['assignmentstobegraded'] = 'Assignments to be Graded';
$string['assignment'] = 'Assignment';
$string['recentfeedback'] = 'Recent Feedback';

// Recent Events
$string['upcomingevents'] = 'Upcoming Events';
$string['productimage'] = 'Product Image';
$string['noupcomingeventstoshow'] = 'There are no upcoming Events to show!';
$string['viewallevents'] = 'View All Events';
$string['addnewevent'] = 'Add new Event';

// Enrolled users stats
$string['enrolleduserstats'] = 'Enrolled Users Stats for Course Categories';
$string['problemwhileloadingdata'] = 'Sorry, Some problem occured while loading data.';
$string['nocoursecategoryfound'] = 'No Course categories found in the System.';
$string['nousersincoursecategoryfound'] = 'No enrolled users found in this Course Category.';

// Quiz stats
$string['quizstats'] = 'Quiz Attempt Stats for Courses';
$string['totalusersattemptedquiz'] = 'Total Users attempted Quiz';
$string['totalusersnotattemptedquiz'] = 'Total Users not attempted Quiz';

/* Theme Controller */

$string['years'] = 'year(s)';
$string['months'] = 'month(s)';
$string['days'] = 'day(s)';
$string['hours'] = 'hour(s)';
$string['mins'] = 'min(s)';

$string['parametermustbeobjectorintegerorstring'] = 'paramater {$a} must be an object or an integer or a numeric string';
$string['usernotenrolledoncoursewithcapability'] = 'User not enrolled on course with capability';
$string['userdoesnothaverequiredcoursecapability'] = 'User does not have required course capability';
$string['coursesetuptonotshowgradebook'] = 'Course set up to not show gradebook to students';
$string['coursegradeishiddenfromstudents'] = 'Course grade is hidden from students';
$string['feedbackavailable'] = 'Feedback available';
$string['nograding'] = 'You have no submissions to grade.';


/* Calendar page */
$string['selectcoursetoaddactivity'] = 'Select Course to add an Activity';
$string['addnewactivity'] = 'Add new Activity';

// Calendar page js
$string['redirectingtocourse'] = 'Redirecting to {$a} Course page..';
$string['nopermissiontoaddactivityinanycourse'] = 'Sorry, You don\'t have permission to Add Activity in any Course.';
$string['nopermissiontoaddactivityincourse'] = 'Sorry, You don\'t have permission to Add Activity in {$a} Course.';
$string['selectyouroption'] = 'Select your option';


/* Blog Archive page */
$string['viewblog'] = 'View full Blog';


/* Course js */

$string['hidesection'] = 'Collapse';
$string['showsection'] = 'Expand';
$string['hidesections'] = 'Collapse Sections';
$string['showsections'] = 'Expand Sections';
$string['addsection'] = 'Add Section';

$string['overdue'] = 'Overdue';
$string['due'] = 'Due';
