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

namespace tool_musudo\muform\userpicker;

use tool_mulib\local\sql;
use tool_musudo\local\util;

/**
 * Candidates for new privileged user: not an admin and not privileged yet.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer_create_userid extends \tool_mulib\muform\userpicker\base {
    /**
     * Constructor.
     */
    public function __construct() {
        util::require_admin();
    }

    #[\Override]
    public function get_args(): array {
        return [];
    }

    #[\Override]
    protected function get_context(): \core\context {
        return \core\context\system::instance();
    }

    /**
     * Admins and privileged users are not candidates.
     *
     * @return sql|null
     */
    #[\Override]
    protected function get_candidates_sql(): ?sql {
        global $CFG;
        $admins = array_map('intval', explode(',', $CFG->siteadmins));
        return new sql(
            "u.id NOT IN (" . implode(',', $admins) . ")
             AND NOT EXISTS (SELECT 'x' FROM {tool_musudo_sudoer} su WHERE su.userid = u.id)"
        );
    }
}
