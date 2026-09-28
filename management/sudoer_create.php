<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Create sudoer.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\muform\handler;
use tool_musudo\local\form\sudoer_create;
use tool_musudo\local\sudoer;
use tool_musudo\local\util;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var stdClass $CFG */

require('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$pageurl = new core\url('/admin/tool/musudo/management/sudoer_create.php');
admin_externalpage_setup('tool_musudo_sudoers', '', null, $pageurl, ['pagelayout' => 'report', 'nosearch' => true]);

util::require_admin();

$title = get_string('sudoer_create', 'tool_musudo');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$returnurl = new core\url('/admin/tool/musudo/index.php');

$handler = handler::from_request();

$form = new sudoer_create($pageurl, ['level_0' => 'system']);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    sudoer::create(sudoer_create::get_privileges_data($data));
    $handler->submitted($returnurl);
}

$handler->render($form);
