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

namespace tool_musudo\local\form;

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;
use tool_musudo\local\mfa;

/**
 * Update sudo user.
 *
 * @package    tool_musudo
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer_update extends form {
    use privileges_trait;

    #[\Override]
    protected function definition(): void {
        $this->add(new info('username', get_string('user')));

        $this->add(new textarea('note', get_string('sudoer_note', 'tool_musudo'), ['rows' => 3]));

        if (mfa::is_mfa_enabled()) {
            $this->add(new checkbox('mfarequired', get_string('mfarequired', 'tool_musudo')));
        }

        // Dealing with deleted roles would be a hassle here...
        $this->add_privileges();

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('sudoer_update', 'tool_musudo')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $this->validate_privileges($data, $allerrors);
    }
}
