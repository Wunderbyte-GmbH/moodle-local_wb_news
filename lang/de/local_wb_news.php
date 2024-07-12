<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_wb_news
 * @category    string
 * @copyright   2024 Thomas Winkler <stephan.lorbek@uni-graz.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'News Manager';
$string['wb_news'] = 'News Manager';
$string['manage'] = 'Bearbeiten';
$string['delete'] = 'Entfernen';
$string['form_submit'] = 'Speichern';
$string['form_cover'] = 'Coverbild';
$string['form_date'] = 'Datum';
$string['form_content'] = 'Inhalt';
$string['form_title'] = 'Titel';
$string['redirect'] = "Sie werden gleich weitergeleitet";
$string['success'] = 'News erfolgreich gespeichert!';
$string['manage_new'] = 'Neue News anlegen';
$string['news_updated'] = 'Neuigkeiteneintrag aktualisiert';
$string['news_created'] = 'Neuigkeiteneintrag erstellt';
$string['news_deleted'] = 'Neuigkeiteneintrag entfernt';

$string['wb_news:manage'] = "Verwaltung von News";
$string['wb_news:view'] = "Zugriff auf News";

$string['addnewnews'] = 'Füge Eintrag hinzu';
$string['editnewnews'] = 'Bearbeite Eintrag';
$string['deletenewnews'] = 'Lösche Eintrag';

$string['addinstance'] = 'Füge Instanz hinzu';
$string['editinstance'] = 'Bearbeite Instanz';
$string['deleteinstance'] = 'Lösche Instanz';

$string['addeditform'] = 'Hinzufügen oder bearbeiten eines Eintrags';
$string['instanceid'] = 'Instanz-ID';
$string['activenews'] = 'Beim ersten Laden aktiv';
$string['icon'] = 'Icon';
$string['sortorder'] = 'Niedrig wird vorgereiht';
$string['bgimage'] = 'Hauptbild';
$string['bgcolor'] = 'Farbe als Hex-Code';
$string['novalidhexcolor'] = 'Das ist kein gültiger Hex-Code';
$string['imagemode'] = 'Anzeige des Bildes';
$string['useasbgimage'] = 'Als Hintergrundbild verwenden';
$string['useasheaderimage'] = 'Als Kopfzeilenbild verwenden';
$string['icon'] = 'Symbol';
$string['userid'] = 'Benutzer-ID';
$string['headline'] = 'Überschrift';
$string['subheadline'] = 'Unterüberschrift';
$string['description'] = 'Beschreibung';
$string['btnlink'] = 'Schaltflächenlink';
$string['btntext'] = 'Schaltflächentext';
$string['lightmode'] = 'Heller Modus';
$string['cssclasses'] = 'Zusätzliche CSS Klassen';
$string['columns'] = 'Spalten';
$string['keyvaluepairs'] = 'Schlüssel: Wert';
$string['additionaldata'] = 'Zusatzdaten';
$string['timelinetemplate'] = 'Timeline Vorlage';
$string['timelinetemplate2'] = 'Timeline Modus2 Vorlage';


$string['template'] = 'Vorlage';
$string['name'] = 'Name';
$string['masonrytemplate'] = 'Bausteine Vorlage';
$string['slidertemplate'] = 'Slider Vorlage';
$string['tabstemplate'] = 'Tabs Vorlage';
$string['gridtemplate'] = 'Gitter Vorlage';
$string['blogtemplate'] = 'Blog Vorlage';
$string['crosslinkstemplate'] = 'Crosslinks Vorlage';

// Modal.
$string['confirmdelete'] = "Bestätige das Löschen. Dieser Vorgang kann nicht rückgängig gemacht werden.";
$string['deletenewsitem'] = "Bestätige das Löschen dieses Artikels";
$string['deleteinstance'] = "Bestätige des Löschens aller Artikel und der Instanz";
$string['confirmcopy'] = "Bestätige das Kopieren";
$string['copyitem'] = "Kopieren";
$string['alloweditincontext'] = "Editieren erlauben in...";
$string['system'] = "Systemweit";

// Errors.
$string['interror'] = 'Nur ganze Zahlen sind erlaubt.';

// Shortcodes.
$string['wbnewslist'] = 'Eine oder alle News Instanzen.';
$string['novalidinstance'] = 'Das ist keine verfügbare Instanz mit der id {$a}';
$string['tagarea_news'] = "WB News";
