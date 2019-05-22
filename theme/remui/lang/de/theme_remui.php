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
$string['region-side-post'] = 'Rechts';
$string['region-side-pre'] = 'Links';
$string['fullscreen'] = 'Vollbild';
$string['closefullscreen'] = 'Vollbild schließen';
$string['licensesettings'] = 'Lizenz Einstellungen';
$string['edwiserremuilicenseactivation'] = 'Edwiser RemUI Lizenz Aktivierung';
$string['overview'] = 'Übersicht';
$string['choosereadme'] = '
<div class="about-remui-wrapper" align="center">
    <div class="about-remui" style="max-width: 800px;">
        <h1 class="text-center">Willkommen bei Edwiser RemUI</h1><br>
        <h4 class="text-muted">
     Edwiser RemUI ist die neue Revolution in der Benutzererfahrung. Es wurde so gestaltet, dass die die Benutzerfreundlichkeit erhöt wurde, mit einer einfachen Navigation, Inhaltserstellung und Anpassungsoptionen <br><br>
Wir sind uns sicher, dass Sie das neue Aussehen mögen werden.  </h4>
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
                <a href="https://edwiser.org/remui/documentation/" target="_blank" class="btn btn-primary">Dokumentation</a>&nbsp;
              </div>
              <div class="btn-group" role="group">
                <a href="https://edwiser.org/contact-us/" target="_blank" class="btn btn-primary">Support</a>
              </div>
            </div>
        </div>
        <br>
        <h1 class="text-center">Ihr Theme personalisieren </h1>
        <h4 class="text-muted text-center">

            Wir verstehen, dass nicht jedes LMS gleich ist.
            Wir werden mit Ihenen zusammenarbeiten um Ihre Anforderungen zu verstehen um eine Lösung zu entwickeln und zu designen, die Ihren Vorstellungen entspricht.
        </h4>
        <br><br>
        <div class="row wdm_generalbox">
            <div class="iconbox span3 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="iconcircle">
                    <i class="fa fa-cogs"></i>
                </div>
                <div class="iconbox-content">
                    <h4>Theme Anpassung</h4>
                </div>
            </div>
            <div class="iconbox span3 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <div class="iconcircle">
                    <i class="fa fa-edit"></i>
                </div>
                <div class="iconbox-content">
                    <h4>Funktionalität Entwicklung</h4>
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
                    <h4>LMS Beratung</h4>
                </div>
            </div>
        </div>
        <br>
        <br>
        <div class="text-center">
            <a class="btn btn-primary btn-lg" target="_blank" href="https://edwiser.org/contact-us/">Uns kontaktieren</a>&nbsp;&nbsp;
        </div>
    </div>
</div>
<br />';

$string['licensenotactive'] = '<strong>Alert!</strong>Lizenz ist nicht aktiviert, bitte <strong>aktivieren</strong> Sie die Lizenz in den RemUI Einstellungen.';
$string['licensenotactiveadmin'] = '<strong>Alert!</strong> Lizenz ist nicht aktiviert, bitte <strong>aktivieren</strong> Sie die Lizenz<a href="'.$CFG->wwwroot.'/theme/remui/remui_license.php" >hier</a>.';
$string['activatelicense'] = 'Lizenz aktivieren';
$string['deactivatelicense'] = 'Lizenz deaktivieren';
$string['renewlicense'] = 'Lizenz erneuern';
$string['active'] = 'Aktiv';
$string['notactive'] = 'Nicht Aktiv';
$string['expired'] = 'Abgelaufen';
$string['licensekey'] = 'Lizenzschlüssel';
$string['licensestatus'] = 'Lizen Status';
$string['noresponsereceived'] = 'Keinen Antwort von dem Server. Bitte später noch einmal versuchen.';
$string['licensekeydeactivated'] = 'Lizenzschlüssel ist deaktiviert.';
$string['siteinactive'] = 'Seite ist deaktiviert (Auf Lizenz aktivieren klicken, um das Plugin zu aktivieren).';
$string['entervalidlicensekey'] = 'Bitte den gültigen Lizenzschlüssel eingeben.';
$string['licensekeyisdisabled'] = 'Ihr Lizenzschlüssel ist deaktiviert.';
$string['licensekeyhasexpired'] = "Ihr Lizenzschlüssel ist abgelaufen. Bitte erneuern Sie ihn.";
$string['licensekeyactivated'] = "Ihr Lizenzschlüssel ist aktiviert.";
$string['enterlicensekey'] = "Bitte den Lizenzschlüssel eingeben.";

// course
$string['nosummary'] = 'Es wurde keine Zusammenfassung in dem Kurs hinzugefügt.';
$string['defaultimg'] = 'Voreingestelltes Bild 100 x 100.';
$string['choosecategory'] = 'Kategorie auswählen';
$string['allcategory'] = 'Alle Kategorien';
$string['viewcours'] = 'Kurs ansehen';
$string['taught-by'] = 'Lehrer';

// Dashboard Element -> overview
$string['totaldiskusage'] = 'Gesamte Speicherplatznutzung';
$string['activemembers'] = 'Aktive Mitglieder';
$string['newmembers'] = 'Neue Mitgliedern';
$string['coursesdiskusage'] = 'Kurs - Speicherplatznutzung';
$string['activestudents'] = 'Aktive Studenten';

// Schnelle Nachricht
$string['quickmessage'] = 'Schnelle Nachricht';
$string['entermessage'] = 'Bitte eine Nachricht eingeben!';
$string['selectcontact'] = 'Bitte einen Kontakt auswählen!';
$string['selectacontact'] = 'Einen Kontakt auswählen';
$string['sendmessage'] = 'Nachricht schicken';
$string['yourcontactlisistempty'] = 'Ihre Kontaktliste ist leer!';
$string['viewallmessages'] = 'Alle Nachrichten sehen';
$string['messagesent'] = 'Erfolgreich gesendet!';
$string['messagenotsent'] = 'Nachricht nicht gesendet! Geben Sie bitte die richtige Werte ein.';
$string['messagenotsenterror'] = 'Nachricht nicht gesendet! Etwas ist schief gelaufen.';
$string['sendingmessage'] = 'Nachricht wird gesendet...';
$string['sendmoremessage'] = 'Weitere Nachricht senden';

// Allgemeine Einstellungen
$string['generalsettings' ] = 'Allgemeine Einstellungen';
$string['navsettings'] = 'Nav Einstellungen';
$string['homepagesettings'] = 'Startseiten Einstellungen';
$string['colorsettings'] = 'Farbeinstellungen';
$string['fontsettings' ] = 'Schriftart Einstellungen';
$string['slidersettings'] = 'Schieberegler-Einstellungen';
$string['configtitle'] = 'Edwiser RemUI';

// Font Einstellungen.
$string['fontselect'] = 'Schriftart Auswähler';
$string['fontselectdesc'] = 'Wählen Sie entweder die Standardmäßigen Schriftart oder Google Web Font. Bitte zuerst speichern, um die Optionen für Ihre Auswahl zu zeigen.';
$string['fonttypestandard'] = 'Standardmäßige Schriftart';
$string['fonttypegoogle'] = 'Google web Font';
$string['fontnameheading'] = 'Überschrift- Font';
$string['fontnameheadingdesc'] = 'Den genauen Schriftartnamen für die Überschriften eingeben.';
$string['fontnamebody'] = 'Text Font';
$string['fontnamebodydesc'] = 'Geben Sie den genauen Namen für die Schriftart ein, um diese für alle anderen Texte zu nutzten.';

/* Dashboard Einstellungen*/
$string['dashboardsetting'] = 'Dashboard Einstellungen';
$string['themecolor'] = 'Theme Farbe';
$string['themecolordesc'] = 'Welche Farbe soll Ihr Theme haben. Dies wird viele Komponenten ändern, um die gewünschte Farbe auf den Moodle Seiten zu erzeugen.';
$string['themetextcolor'] = 'Text Farbe';
$string['themetextcolordesc'] = 'Wählen Sie die Farbe für Ihren Text.';
$string['layout'] = 'Layout auswählen';
$string['layoutdesc'] = 'Aktivieren Sie das Layout entweder das Fixed Layout ( Kopfzeilen Menü wird oben fest stehen) oder aus dem Deafault Layout. '; // Boxed Layout oder
$string['defaultlayout'] = 'Voreinstellung';
$string['fixedlayout'] = 'Feste Kopfzeile';
$string['defaultboxed'] = 'Boxed';
$string['layoutimage'] = 'Boxed Layout Hintergrundbild';
$string['layoutimagedesc'] = 'Das Hintergrundbild hochladen, um es auf das Boxed Layout anzulegen .';
$string['rightsidebarslide'] = 'Rechte Seitenleiste umschalten';
$string['rightsidebarslidedesc'] = 'Standardmäßit die rechte Seitenleiste umschalte.';
$string['leftsidebarslide'] = 'Linke Seitenleiste umschalten';
$string['leftsidebarslidedesc'] = 'Standardmäßit die linke Seitenleiste umschalte..';
$string['rightsidebarskin'] = 'Rechte Seitenleisteoberfläche umschalten ';
$string['rightsidebarskindesc'] = 'Rechte Seitenleisteoberfläche ändern.';

/*Farbe*/
$string['colorscheme'] = 'Ein Farbschema auswählen';
$string['colorschemedesc'] = 'Sie können ein Farbschema für Ihre Website aus diesesn Optionen auswählen- Blau, Schwarz, Purpur, Grün, Gelb, Blau-hell, Purpur-hell, Grün-hell und Gelb-hell. <br /> <b>Hell</b> - gibt einen hellen Hintergrund für Ihr left side bar.';
$string['blue'] = 'Blau';
$string['white'] = 'Weiß';
$string['purple'] = 'Purpur';
$string['green'] = 'Grün';
$string['red'] = 'Rot';
$string['yellow'] = 'Gelb';
$string['bluelight'] = 'Blau hell';
$string['whitelight'] = 'Weiß hell';
$string['purplelight'] = 'Purpur hell';
$string['greenlight'] = 'Grün hell';
$string['redlight'] = 'Rot hell';
$string['yellowlight'] = 'Gelb hell';
$string['custom'] = 'Dunkel-kundenspezifisch';
$string['customlight'] = 'Hell-kundenspezifisch';
$string['customskin_color'] = 'Skin Farbe';
$string['customskin_color_desc'] = 'Sie können eine custom Farbe für Ihr Theme hier auswählen.';

/* Kurseinstellungen*/
$string['courseperpage'] = 'Kurse pro Seite';
$string['courseperpagedesc'] = 'Die Anzahl von Kursen, die pro Seite in der Kurs Archiv Seite angezeigt werden soll.';
$string['nocoursefound'] = 'Keinen Kurs gefunden';

/*logo*/
$string['logo'] = 'Logo';
$string['logodesc'] = 'Sie können ein Logo hinzufügen, um es in der Kopfzeile anzuzeigen. Hinweis - Bevorzugte Größe ist 50px. Falls Sie es anpassen wollen, können Sie es aus der Custom CSS Box tun.';
$string['siteicon'] = 'Website-Symbol';
$string['siteicondesc'] = 'Haben Sie kein Logo? Sie können eines aus dieser Liste auswählen <a href="http://fortawesome.github.io/Font-Awesome/cheatsheet/" target="_new">hier</a>. Geben Sie nur das ein, was nach dem "fa-" kommt. ';
$string['logoorsitename'] = 'Das Logo Format auswählen';
$string['logoorsitenamedesc'] = 'Sie können das Aussehen des Seiten Kopfzeile-Logos ändern. Die verfügbare Möglichkeiten sind: Logo- Nur das Logo wird gezeigt; Seitename - Nur der Seitennamen wird gezeigt; Icon+Sitename - Ein Icon zusammen mit einem Seitennamen wird gezeigt.';
$string['onlylogo'] = 'Nur Logo';
$string['onlysitename'] = 'Nur Seitenname';
$string['iconsitename'] = 'Icon und Seitenname';

/*favicon*/
$string['favicon'] = 'Favicon';
$string['favicondesc'] = 'Ihr Website "Lieblings Icon". Hier können Sie Ihr Favicon für Ihre Website einsetzen.';
$string['enablehomedesc'] = 'Home Desc aktivieren';

/*custom css*/
$string['customcss'] = 'Custom CSS';
$string['customcssdesc'] = 'Sie können das CSS aus der Text Box anpassen. Diese Veränderungen werden auf alle Seiten Ihrer Installation angelegt.';

/*google analytics*/
$string['googleanalytics'] = 'Google Analytics Tracking ID';
$string['googleanalyticsdesc'] = 'Bitte geben Sie Ihre  Google Analytics Tracking ID ein, um Analyticsauf Ihre Website zu aktivieren . Der  tracking ID Format sollte so sein  [UA-XXXXX-Y]';

/*theme_remUI_startseite*/

$string['frontpageimagecontent'] = 'Inhalt von Kopfzeile';
$string['frontpageimagecontentdesc'] = ' Dieses Teil bezieht sich auf den obene Abschnitt Ihrer Startseite';
$string['frontpageimagecontentstyle'] = 'Stil';
$string['frontpageimagecontentstyledesc'] = 'Sie können aus statischen Inhalten und Slider Inhalten auswählen.';
$string['staticcontent'] = 'Statische Inhalte';
$string['slidercontent'] = 'Slider Inhalte';
$string['addtext'] = 'Text hinzufügen';
$string['defaultaddtext'] = 'Bildung ist einen lange erprobt Weg zum Erfolg.';
$string['addtextdesc'] = 'Hier können Sie den Text hinzufügen, der auf der Startseite gezeigt werden soll, möglichst in HTML.';
$string['uploadimage'] = 'Bild hochladen';
$string['uploadimagedesc'] = 'Hier können Sie Ihr eigenes Bild hochladen ';
$string['video'] = 'iframe Embedded code';
$string['videodesc'] = 'Hier können Sie den iframe Embedded Code des Videos einsetzen, der integriert werden soll.';
$string['contenttype'] = 'Art des Inhalts auswählen';
$string['contentdesc'] = 'Sie können aus dem Bild oder Video url auswählen.';
$string['image'] = 'Bild';
$string['videourl'] = 'Video URL';
$string['frontpageimge'] = '';

$string['slidercount'] = 'Anzahl von Folien ';
$string['slidercountdesc'] = '';
$string['one'] = '1';
$string['two'] = '2';
$string['three'] = '3';
$string['four'] = '4';
$string['five'] = '5';
$string['eight'] = '8';
$string['twelve'] = '12';

$string['slideimage'] = 'Bilder für Slider hochladen';
$string['slideimagedesc'] = 'Sie können hier ein Bild als Inhalt für den Slider hochladen.';
$string['slidertext'] = 'Slider Text hinzufügen';
$string['defaultslidertext'] = '<h2><span>Bildung ist einen lange erprobt Weg zum Erfolg</span><br>YOU ENTER TO LEARN, LEAVE TO ACHIEVE</h2><p>Education ignites a purpose within us and beckons us on a path of enlightenment. It allows for a progressive mind to flourish that builds a self-sustaining society.</p>';
$string['slidertextdesc'] = 'Sie können den Text Inhalt von Ihrem Slider einsetzen, möglichst im HTML.';
$string['sliderurl'] = 'Slider Text Button Link';
$string['sliderbuttontext'] = 'Text Button von Slider hinzufügen ';
$string['sliderbuttontextdesc'] = 'Sie können eine Text Button auf Ihr Slider einsetzen.';
$string['sliderurldesc'] = 'Sie können den Link der Seite einsetzen, wo der Benutzer nur einmal umgeleitet werden, nachdem er/sie auf die Text Button klickt.';
$string['slideinterval'] = 'Slide Interval';
$string['slideintervaldesc'] = 'Sie können die Übergangszeit zwischen den Folien einstellen. Falls es nur eine Folie gibt, wird diese Option gar keinen Einfluss darauf haben. ';
$string['sliderautoplay'] = 'Slider Autoplay Einstellen';
$string['sliderautoplaydesc'] = 'Ja auswählen, wenn Sie einen automatischen Übergang in Ihrer Slideshow haben möchten.';
$string['true'] = 'Ja';
$string['false'] = 'Nein';

$string['frontpageblocks'] = 'Body Inhalt';
$string['frontpageblocksdesc'] = 'Sie können eine Überschrift für Ihren Webseiten-Inhalt einsetzen';

$string['enablesectionbutton'] = 'Buttons in diesem Abschnitt aktivieren ';
$string['enablesectionbuttondesc'] = 'Buttons auf dem Body Abschnitt aktivieren.';

/* General section descriptions */
$string['frontpageblockiconsectiondesc'] = 'Sie können irgendein Icon auswählen <a href="http://fortawesome.github.io/Font-Awesome/cheatsheet/" target="_new">list</a>. Geben Sie den Text erst nach "fa-". ';
$string['frontpageblockdescriptionsectiondesc'] = 'Eine kurze Beschreibung über den Titel';
$string['defaultdescriptionsection'] = 'Holistisch nutzen die Technologie rechtzeitig durch Unternehmenfällen';
$string['sectionbuttontextdesc'] = 'Geben Sie den Text für den Button in diesem Teil .';
$string['sectionbuttonlinkdesc'] = 'Geben Sie die URL für diesen Teil.';
$string['frontpageblocksectiondesc'] = 'Titel zu diesem Teil hinzufügen .';

/*block Teil 1*/
$string['frontpageblocksection1'] = 'Body Titel für den erste Teil';
$string['frontpageblockdescriptionsection1'] = 'Body Beschreibung für den erste Teil';
$string['frontpageblockiconsection1'] = 'Font-Awesome iocn Teil 1';
$string['sectionbuttontext1'] = 'Button Text für Teil1';
$string['sectionbuttonlink1'] = 'URL Link Teil1';


/*block Teil 2*/
$string['frontpageblocksection2'] = 'Body Titel für den zweite Teil';
$string['frontpageblockdescriptionsection2'] = 'Body Beschreibung für den zweite Teil';
$string['frontpageblockiconsection2'] = 'Font-Awesome iocn Teil  2';
$string['sectionbuttontext2'] = 'Button Text für Teil2';
$string['sectionbuttonlink2'] = 'URL Link Teil2';


/*block Teil 3*/
$string['frontpageblocksection3'] = 'Body Titel für dritte Teil';
$string['frontpageblockdescriptionsection3'] = 'Body Beschreibung für das dritte Teil';
$string['frontpageblockiconsection3'] = 'Font-Awesome iocn Teil  3';
$string['sectionbuttontext3'] = 'Button Text für Teil3';
$string['sectionbuttonlink3'] = 'URL Link Teil3';


/*block Teil 4*/
$string['frontpageblocksection4'] = 'Body Titel für den vierte Teil';
$string['frontpageblockdescriptionsection4'] = 'Body Beschreibung für den vierte Teil';
$string['frontpageblockiconsection4'] = 'Font-Awesome iocn Teil  4';
$string['sectionbuttontext4'] = 'Button Text für Teil4';
$string['sectionbuttonlink4'] = 'URL Link für Teil4';


// Startseite Überuns Einstellungen
$string['frontpageaboutus'] = 'Startseite Über Uns ';
$string['frontpageaboutusdesc'] = 'Dieser Teil ist für die Starteseite Über uns';

$string['enablefrontpageaboutus'] = 'Über uns Teil aktivieren';
$string['enablefrontpageaboutusdesc'] = 'Den Über uns Teil auf die Startseite aktivieren.';
$string['frontpageaboutusheading'] = 'Über uns Überschrift';
$string['frontpageaboutusheadingdesc'] = 'Überschrift für default heading Text für diesen Teil ';
$string['frontpageaboutustext'] = 'Über uns Text';
$string['frontpageaboutustextdesc'] = 'Geben Sie den über uns Text für die Startseite ein.';
$string['frontpageaboutusdefault'] = '<p class="lead">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
              Ut enim ad minim veniam.</p>
              <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
                  eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.Lorem ipsum dolor sit amet,
                  consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
                  labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur
                  adipisicing elit, sed do eiusmod tempor incididunt
                  ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p>';
$string['frontpageaboutusimage'] = 'Startseite über uns Bild';
$string['frontpageaboutusimagedesc'] = 'Das Bild für Startseite über uns Teil hochladen';


$string['socialmedia'] = 'Social Media Einstellungen';
$string['socialmediadesc'] = 'Geben Sie den social media Link für Ihre Seite ein.';
$string['facebooksetting'] = 'Facebook Einstellungen';
$string['facebooksettingdesc'] = 'Geben Sie Ihren Facebook Link ein. Zum Beispiel. https://www.facebook.com/EndlessBrain';
$string['twittersetting'] = 'Twitter Einstellungen';
$string['twittersettingdesc'] = 'Geben Sie Ihren Twitter Link ein. Zum Beispiel. https://www.twitter.com/EndlessBrain';
$string['linkedinsetting'] = 'Linkedin Einstellungen';
$string['linkedinsettingdesc'] = 'Geben Sie Ihren Linkedin Link ein. Zum Beispiel. https://www.linkedin.com/in/pagename <br/> Falls Ihr Land Indien ist.';
$string['gplussetting'] = 'Google Plus Einstellungen';
$string['gplussettingdesc'] = 'Geben Sie Ihren Google Plus Page Link ein. Zum Beispiel. https://plus.google.com/pagename';
$string['youtubesetting'] = 'YouTube Einstellungen';
$string['youtubesettingdesc'] = 'Geben Sie Ihren YouTube Link ein.Zum Beispiel. https://www.youtube.com/channel/UCU1u6QtAAPJrV0v0_c2EISA';
$string['instagramsetting'] = 'Instagram Einstellungen';
$string['instagramsettingdesc'] = 'Geben Sie Ihren Instagram Link ein. Zum Beispiel. https://www.linkedin.com/company/name';
$string['pinterestsetting'] = 'Pinterest Einstellungen';
$string['pinterestsettingdesc'] = 'Geben Sie Ihren Pinterest Link ein. Zum Beispiel. https://www.pinterest.com/name';


// Fußzeile Teil Einstellungen
$string['footersetting'] = 'Fußzeile Einstellungen';
// Fußzeile Spalte 1
$string['footercolumn1heading'] = 'Fußzeile Inhalt für Spalte 1 (Links)';
$string['footercolumn1headingdesc'] = 'Dieses Teil bezieht sich auf den unteren Teil ( Spalte 1) von Ihrer Startseite.';

$string['footercolumn1title'] = 'Fußzeile Spalte 1 Titel ';
$string['footercolumn1titledesc'] = 'Hier können Sie einen Titel für die erste Spalte der Fußzeile hinzufügen.';
$string['footercolumn1customhtml'] = 'Custom HTML';
$string['footercolumn1customhtmldesc'] = 'Sie können HTML für Fußzeile Spalte 1 von der oberen Text Box anpassen.';


// Footer  Column 2
$string['footercolumn2heading'] = 'Fußzeile Inhalt für Spalte  2 (Mittel)';
$string['footercolumn2headingdesc'] = 'Dieser Teil bezieht sich auf den untere Teil ( Spalte 2) von Ihrer Startseite.';

$string['footercolumn2title'] = 'Fußzeile Spalte 2 Titel';
$string['footercolumn2titledesc'] = 'Hier können Sie einen Titel für die erste Spalte der Fußzeile hinzufügen.';
$string['footercolumn2customhtml'] = 'Custom HTML';
$string['footercolumn2customhtmldesc'] = 'Sie können HTML für Fußzeile Spalte 2 von der oberen Text Box anpassen.';

// Footer  Column 3
$string['footercolumn3heading'] = 'Fußzeile Inhalt für Spalte  3 (Rechts)';
$string['footercolumn3headingdesc'] = 'Dieser Teil bezieht sich auf den unteren Teil ( Spalte 3) von Ihrer Startseite.';

$string['footercolumn3title'] = 'Fußzeile Spalte 3 Titel';
$string['footercolumn3titledesc'] = 'Hier können Sie einen Titel für die erste Spalte der Fußzeile hinzufügen.';
$string['footercolumn3customhtml'] = 'Custom HTML';
$string['footercolumn3customhtmldesc'] = 'Sie können HTML für Fußzeile Spalte 3 von der oberen Text Box anpassen.';

// Fußzeile Unten-Rechts Teil
$string['footerbottomheading'] = 'Einstellung Fußzeile Unterer Teil ';
$string['footerbottomdesc'] = 'Hier können Sie Ihren eigene Link einfügen, den Sie in dem unteren Teil der Fußzeile eingeben wollen.';
$string['footerbottomtext'] = 'Fußzeile Unten-Rechts Text';
$string['footerbottomtextdesc'] = 'Geben Sie den Text für das Unten-Rechts Teil der Fußzeile ein.';
$string['footerbottomlink'] = 'Fußzeile Unten-Rechts Link ';
$string['footerbottomlinkdesc'] = 'Geben Sie den Link für das unten-recht Teil der Fußzeile. Zum Beispiel. http://www.EndlessBrain.com';


// Log-in Einstellungen Seite Code beginnt.

$string['loginsettings'] = 'Einstellungen Log-in Seite ';
$string['navlogin_popup'] = ' Login Popup aktivieren';
$string['navlogin_popupdesc'] = ' Login popup in Kopfzeile aktivieren.';
$string['loginsettingpic'] = 'Hier ein Bild hochladen';
$string['loginsettingpicdesc'] = 'Dieses Bild wird in dem Hintergrund des Log-in Formulars angezeigt.';
$string['signuptextcolor'] = 'Registrierungs Panel Textfarbe';
$string['signuptextcolordesc'] = 'Wählen Sie die Textfarbe für das Registrierungs Panel aus, die zu Ihrem Hintergrund Bild Ihrer Log-in Seite passt.';
$string['left'] = "Links";
$string['right'] = "Rechts";

// Log-in Einstellungen Seite Code beendet.


// von theme snap
$string['title'] = 'Titel';
$string['contents'] = 'Inhalte';
$string['addanewsection'] = 'Ein neuen Inhalt erstellen';
$string['createsection'] = 'Inhalt erstellen';



/* Benutzerprofil Seite*/

$string['blogentries'] = 'Blog Einträge';
$string['discussions'] = 'Diskussionen';
$string['discussionreplies'] = 'Antworten';
$string['aboutme'] = 'Über mich';

$string['addtocontacts'] = 'Zu Kontakten hinzufügen';
$string['removefromcontacts'] = 'Aus Kontakten entfernen ';
$string['block'] = 'Blockieren';
$string['removeblock'] = 'Blockierung entfernen';

$string['interests'] = 'Interesse';
$string['institution'] = 'Institution';
$string['location'] = 'Ort';
$string['description'] = 'Beschreibung';

$string['commoncourses'] = 'Gemeinsame Kursen';
$string['editprofile'] = 'Profil bearbeiten';

$string['firstname'] = 'Vorname';
$string['surname'] = 'Nachname';
$string['email'] = 'Email';
$string['citytown'] = 'Stadt';
$string['country'] = 'Land';
$string['selectcountry'] = 'Land auswählen';
$string['description'] = 'Beschreibung';

$string['nocommoncourses'] = 'Sie haben sich mit diesem Benutzer in keinem gemeinsamen Kursen angemeldet.';
$string['notenrolledanycourse'] = 'Sie haben sich für keinen angemeldet.';
$string['nobadgesyetcurrent'] = 'Sie haben noch keine Abzeichen.';
$string['nobadgesyetother'] = 'Dieser Benutzer hat noch keine Abzeichen.';

// Benutzerprofil Seite js

$string['actioncouldnotbeperformed'] = 'Vorgang konnte nicht ausgeführt werden!';
$string['enterfirstname'] = 'Geben Sie bitte Ihren Vornamen ein.';
$string['enterlastname'] = 'Geben Sie bitte Ihren Nachnamen ein.';
$string['enteremailid'] = 'Geben Sie bitte Ihre Email Adresse ein.';
$string['enterproperemailid'] = 'Geben Sie bitte Ihre richtige Email Adresse ein.';
$string['detailssavedsuccessfully'] = 'Details erfolgreich gespeichert!';



/* Kopfzeile */

$string['startedsince'] = 'Begonnen seit ';
$string['startingin'] = 'beginnt ab ';

$string['userimage'] = 'Benutzer Bild';

$string['seeallmessages'] = 'Alle Nachrichten ansehen';
$string['viewallnotifications'] = 'Alle Benachrichtigungen ansehen';
$string['viewallupcomingevents'] = 'Alle kommenden Events ansehen';

$string['youhavemessages'] = 'Sie haben {$a} ungelesene Nachricht (en)';
$string['youhavenomessages'] = 'Sie haben keine ungelesene Nachrichten';

$string['youhavenotifications'] = 'Sie haben {$a} Benachrichtigungen';
$string['youhavenonotifications'] = 'Sie haben keine Benachrichtigungen';

$string['youhaveupcomingevents'] = 'Sie haben {$a} kommende Event(s)';
$string['youhavenoupcomingevents'] = 'Sie haben keine kommende Events';


/* Dashboard Elemente */

// Hinweise hinzufügen
$string['addnotes'] = 'Hinweise hinzufügen';
$string['selectacourse'] = 'Einen Kurs auswählen';

$string['addsitenote'] = 'Seitenhinweis hinzufügen';
$string['addcoursenote'] = 'Kurshinweis hinzufügen';
$string['addpersonalnote'] = 'persönlichen Hinweis hinzufügen';
$string['deadlines'] = 'Fristen';

// Hinweise hinzufügen js
$string['selectastudent'] = 'Einen Studenten auswählen';
$string['total'] = 'Insgesamt';
$string['nousersenrolledincourse'] = 'Es gibt keine angemeldeten Benutzer im  {$a} Kurs.';
$string['selectcoursetodisplayusers'] = 'Einen Kurs auswählen, um die angemeldeten Benutzer hier zu zeigen.';


// Deadlines
$string['gotocalendar'] = 'Zum Kalender gehen ';
$string['noupcomingdeadlines'] = 'Es gibt keine bevorstehende Frist!';
$string['in'] = 'In';
$string['since'] = 'Seit';

// Neueste Mitgliedern
$string['latestmembers'] = 'Neueste Mitglieder';
$string['viewallusers'] = 'Alle Benutzer sehen';

// Neulich aktive Forums
$string['recentlyactiveforums'] = 'Zuletzt aktive Forums ';

// Neueste Aufgaben
$string['assignmentstobegraded'] = 'Aufgaben zum benoten ';
$string['assignment'] = 'Aufgabe';
$string['recentfeedback'] = 'Neuestes Feedback';

// Neueste Events
$string['upcomingevents'] = 'Kommende Veranstaltungen ';
$string['productimage'] = 'Produkt Bild';
$string['noupcomingeventstoshow'] = 'Es gibt keine bevorstehende Veranstaltungen!';
$string['viewallevents'] = 'Alle Veranstaltungen sehen';
$string['addnewevent'] = 'Neue Veranstaltungen hinzufügen';

// Angemeldete Benutzer statistiken
$string['enrolleduserstats'] = 'Angemeldete Benutzer Statistiken für diese Kurskategorien ';
$string['problemwhileloadingdata'] = 'Sorry, beim laden der Daten ist etwas schief gelaufen.';
$string['nocoursecategoryfound'] = 'Keine Kurskategorien in dem System gefunden.';
$string['nousersincoursecategoryfound'] = 'Keine angemeldeten Benutzer in dieser Kurskategorie gefunden.';

// Quiz statistiken
$string['quizstats'] = 'Quiz Versuche Statistik für diese Kurse';
$string['totalusersattemptedquiz'] = 'Alle Benutzer, die das Quiz versucht haben ';
$string['totalusersnotattemptedquiz'] = 'Alle Benutzer, die das Quiz nicht versucht haben ';

/* Theme Kontroller */

$string['years'] = 'Jahr(e)';
$string['months'] = 'Monat(e)';
$string['days'] = 'Tag(e)';
$string['hours'] = 'Stunde(n)';
$string['mins'] = 'Minute(n)';

$string['parametermustbeobjectorintegerorstring'] = 'Übergabevariable {$a} muss entweder ein Objekt oder ein Integer oder numerische String sein.';
$string['usernotenrolledoncoursewithcapability'] = 'Benutzer wird nicht mit Fähigkeit mit dem Kurs angemeldet werden.';
$string['userdoesnothaverequiredcoursecapability'] = 'Benutzer hat die erforderliche Kurs Fähigkeit nicht.';
$string['coursesetuptonotshowgradebook'] = 'Kurs so eingestellt, dass die Studenten das Notenbuch nicht sehen können.';
$string['coursegradeishiddenfromstudents'] = 'Kurs Noten sind vor den Studenten versteckt.';
$string['feedbackavailable'] = 'Feedback verfügbar';
$string['nograding'] = 'Sie haben nichts eingereicht, was benotet werden kann.';


/* Kalender Seite */
$string['selectcoursetoaddactivity'] = 'Kurs auswählen, um eine Aktivität hinzuzufügen';
$string['addnewactivity'] = 'Neue Aktivität hinzufügen';

// Kalender Seite js
$string['redirectingtocourse'] = 'Zur {$a} Kursseite umleiten..';
$string['nopermissiontoaddactivityinanycourse'] = 'Sie haben keine Erlaubnis, Aktivitäten in einem Kurs hinzuzufügen. .';
$string['nopermissiontoaddactivityincourse'] = 'Sie haben keine Erlaubnis, eine Aktivität in  {$a} Kurs hinzuzufügen.';
$string['selectyouroption'] = 'Ihre Option auswählen ';


/* Blog Archiv Seite */
$string['viewblog'] = 'Den ganzen Blog sehen';


/* Kurs js */

$string['hidesection'] = 'Teil verstecken';
$string['showsection'] = 'Teil zeigen';
$string['hidesections'] = 'Abschnitt verstecken';
$string['showsections'] = 'Abschnitt zeigen';
$string['addsection'] = 'Abschnitt hinzufügen';

$string['overdue'] = 'überfällig';
$string['due'] = 'fällig';