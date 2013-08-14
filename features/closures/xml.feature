Feature: XML Formatter
  In order to print features
  As a feature writer
  I need to have an xml formatter

  Background:
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      features/bootstrap/FeatureContext.php - content
      """
    And a file named "features/support/bootstrap.php" with:
      """
      features/support/bootstrap.php - content
      """
    And a file named "features/steps/math.php" with:
      """
      features/steps/math.php - content
      """

  Scenario: Multiple parameters
    Given a file named "features/World.feature" with:
      """
      features/World.feature - content
      """
    When I run "behat --no-ansi -f html"
    Then it should pass
    And the output should contain:
      """
      output - 1
      """

  Scenario: Scenario outline examples table
    Given a file named "features/World.feature" with:
      """
      features/World.feature - content
      """
    When I run "behat --no-ansi -f html"
    Then the output should contain:
      """
      output -- html 2
      """

  Scenario: Scenario outline examples expanded
    Given a file named "features/World.feature" with:
      """
      features/World.feature - content
      """
    When I run "behat --no-ansi -f html --expand"
    Then the output should contain:
      """
      output - behat --no-ansi -f html --expand
      """

  Scenario: Links to step definitions relative to a remote base
    Given a file named "behat.yml" with:
      """
      behat.yml - content
      """
    And a file named "features/World.feature" with:
      """
      features/World.feature - content
      """
    When I run "behat --no-ansi -c behat.yml -f html"
    Then the output should contain:
      """
      output ::behat --no-ansi -c behat.yml -f html
      """
