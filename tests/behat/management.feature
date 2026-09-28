@tool @tool_musudo @MuTMS
Feature: Test tool_musudo sudoers management
  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | manager1  | First     | Manager   | manager1@example.com |
      | manager2  | Second    | Manager   | manager2@example.com |

  @javascript
  Scenario: Admin may add, update and remove sudoers
    Given I log in as "admin"
    And I navigate to "Users > Permissions > Privileged users" in site administration
    And I should see "No privileged users found."

    When I press "Add privileged user"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | User     | manager1 |
      | roleid_0 | Manager  |
    And I click on "Add privileged user" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name    | Email address        | Note | Privileges        |
      | First Manager | manager1@example.com |      | Manager in System |

    When I press "Add privileged user"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | User     | manager2         |
      | roleid_0 | Teacher          |
      | Note     | Trusted teacher  |
    And I click on "Add privilege" "button" in the "dialog[open]" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | roleid_1            | Manager    |
      | level_1             | category   |
      | categorycontextid_1 | Category 1 |
    And I click on "Add privileged user" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                      |
      | First Manager  | manager1@example.com |                 | Manager in System               |
      | Second Manager | manager2@example.com | Trusted teacher | Teacher in System               |
      | Second Manager | manager2@example.com | Trusted teacher | Manager in Category: Category 1 |

    When I click on "Actions" "link" in the "Second Manager" "table_row"
    And I click on "Update privileged user" "link" in the "Second Manager" "table_row"
    And the following muform fields in the "dialog[open]" "css_element" match:
      | Note                | Trusted teacher  |
      | roleid_0            | Teacher          |
      | level_0             | system           |
      | roleid_1            | Manager          |
      | level_1             | category         |
      | categorycontextid_1 | Category 1       |
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Note              | Semi-trusted         |
      | roleid_1          | Non-editing teacher  |
      | level_1           | course               |
      | coursecontextid_1 | Acceptance test site |
    And I click on "Update privileged user" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                       |
      | First Manager  | manager1@example.com |                 | Manager in System                |
      | Second Manager | manager2@example.com | Semi-trusted    | Teacher in System                |
      | Second Manager | manager2@example.com | Semi-trusted    | Non-editing teacher in Site home |

    When I click on "Actions" "link" in the "Second Manager" "table_row"
    And I click on "Update privileged user" "link" in the "Second Manager" "table_row"
    And I click on "Delete privilege 2" "button" in the "dialog[open]" "css_element"
    And I click on "Update privileged user" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                       |
      | First Manager  | manager1@example.com |                 | Manager in System                |
      | Second Manager | manager2@example.com | Semi-trusted    | Teacher in System                |
    And I should not see "Non-editing teacher"

    When I click on "Actions" "link" in the "Second Manager" "table_row"
    And I click on "Remove privileged user" "link" in the "Second Manager" "table_row"
    And I click on "Remove privileged user" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | First name     | Email address        | Note            | Privileges                       |
      | First Manager  | manager1@example.com |                 | Manager in System                |
    And I should not see "Second Manager"
