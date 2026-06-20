@mod @mod_videoembed
Feature: Configure Video activity appearance
  In order to change the appearance of the Video activity
  As an admin
  I need to configure the Video activity appearance settings

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following "activities" exist:
      | activity   | name       | intro      | course | idnumber |
      | videoembed | VideoName1 | VideoDesc1 | C1     | VIDEO1   |

  @javascript
  Scenario Outline: Hide and display Video activity features
    Given I am on the "VideoName1" "videoembed activity editing" page logged in as admin
    And I expand all fieldsets
    And I set the field "<feature>" to "<value>"
    And I press "Save and display"
    Then I <shouldornot> see "<lookfor>" in the "region-main" "region"

    Examples:
      | feature                      | lookfor        | value | shouldornot |
      | Display activity description | VideoDesc1     | 1     | should      |
      | Display activity description | VideoDesc1     | 0     | should not  |
      | Display last modified date   | Last modified: | 1     | should      |
      | Display last modified date   | Last modified: | 0     | should not  |
