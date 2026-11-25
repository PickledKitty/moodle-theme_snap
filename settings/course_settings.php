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

defined('MOODLE_INTERNAL') || die;// Main settings.
use theme_snap\admin_setting_configradiobuttons;

$snapsettings = new admin_settingpage('themesnapcoursedisplay', get_string('coursedisplay', 'theme_snap'));

// The tab's heading, as on the other tabs.
$snapsettings->add(new admin_setting_heading('theme_snap/coursedisplay_heading',
    new \core\lang_string('coursedisplay', 'theme_snap'), get_string('coursedisplayhelp', 'theme_snap')));

// Resource display options.
$name = 'theme_snap/resourcedisplay';
$title = new \core\lang_string('resourcedisplay', 'theme_snap');
$card = new \core\lang_string('card', 'theme_snap');
$list = new \core\lang_string('list', 'theme_snap');
$radios = array('list' => $list, 'card' => $card);
$default = 'card';
$description = get_string('resourcedisplayhelp', 'theme_snap');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$snapsettings->add($setting);

// Course footer on/off.
$name = 'theme_snap/coursefootertoggle';
$title = new \core\lang_string('coursefootertoggle', 'theme_snap');
$description = new \core\lang_string('coursefootertoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

//TOC collapse setting
$name = 'theme_snap/tableofcontentsbehaviour';
$title = new \core\lang_string('tableofcontentsbehaviour', 'theme_snap');
$description = new \core\lang_string('tableofcontentsbehaviour_desc', 'theme_snap');

$name = 'theme_snap/tableofcontentsbehaviour';
$title = new \core\lang_string('tableofcontentsbehaviour', 'theme_snap');
$description = new \core\lang_string('tableofcontentsbehaviour_desc', 'theme_snap');

$snapsettings->add(new admin_setting_configselect(
    $name,
    $title,
    $description,
    'collapsed',
    [
        'collapsed' => get_string('collapsed', 'theme_snap'),
        'expanded' => get_string('expanded', 'theme_snap'),
    ]
));

// Lazy loading for pages.
$name = 'theme_snap/lazyload_mod_page';
$title = get_string('lazyload_mod_page', 'theme_snap');
$description = get_string('lazyload_mod_page_description', 'theme_snap');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$snapsettings->add($setting);

// The year a course belongs to, for the front page's year tabs and the My courses Year filter.
$snapsettings->add(new \theme_snap\admin_setting_configyearpattern('theme_snap/courseyearpattern',
    new \core\lang_string('courseyearpattern', 'theme_snap'),
    new \core\lang_string('courseyearpattern_desc', 'theme_snap')));

// The front page's course tiles: tabs from checkbox course custom fields, and category words that fill one of them.
$checkboxfields = [];
foreach (\core_course\customfield\course_handler::create()->get_fields() as $field) {
    if ($field->get('type') === 'checkbox') {
        $checkboxfields[$field->get('shortname')] = $field->get_formatted_name();
    }
}
$snapsettings->add(new admin_setting_configmulticheckbox('theme_snap/fptilesfieldtabs',
    new \core\lang_string('fptilesfieldtabs', 'theme_snap'),
    new \core\lang_string('fptilesfieldtabs_desc', 'theme_snap'), [], $checkboxfields));
$snapsettings->add(new admin_setting_configtextarea('theme_snap/fptilescategorywords',
    new \core\lang_string('fptilescategorywords', 'theme_snap'),
    new \core\lang_string('fptilescategorywords_desc', 'theme_snap'), '', PARAM_TEXT, 30, 3));
$snapsettings->add(new admin_setting_configselect('theme_snap/fptilescategorytab',
    new \core\lang_string('fptilescategorytab', 'theme_snap'),
    new \core\lang_string('fptilescategorytab_desc', 'theme_snap'), '',
    ['' => new \core\lang_string('none')] + $checkboxfields));

$settings->add($snapsettings);
