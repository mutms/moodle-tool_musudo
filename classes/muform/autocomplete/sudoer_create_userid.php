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

namespace tool_musudo\muform\autocomplete;

use tool_mulib\local\sql;
use tool_mulib\muform\util\autocomplete\user_trait;
use tool_musudo\local\util;

/**
 * Candidates for new privileged user: not an admin and not privileged yet.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoer_create_userid extends \tool_mulib\muform\autocomplete\base {
    use user_trait;

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
    public function search(string $query, int $maxitems): ?array {
        return $this->search_users(\context_system::instance(), $query, $maxitems, [], $this->get_where());
    }

    #[\Override]
    public function label(string $value): ?string {
        return $this->user_labels(\context_system::instance(), [$value], $this->get_where())[$value] ?? null;
    }

    #[\Override]
    public function validate(string $value): ?string {
        return $this->validate_users([$value])[$value] ?? null;
    }

    /**
     * Admins and privileged users are not candidates.
     *
     * @return sql
     */
    private function get_where(): sql {
        global $CFG;
        $admins = array_map('intval', explode(',', $CFG->siteadmins));
        return new sql(
            "u.id NOT IN (" . implode(',', $admins) . ")
             AND NOT EXISTS (SELECT 'x' FROM {tool_musudo_sudoer} su WHERE su.userid = u.id)"
        );
    }
}
