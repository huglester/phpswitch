Feature: php:switch
  Scenario:
    Given I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch php:switch on"
     Then I should see output matching
        """
        phpswitch is not initialized. Please run init command
        """
      And The command should exit with failure status
      And I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch php:switch off"
     Then I should see output matching
        """
        phpswitch is not initialized. Please run init command
        """
      And The command should exit with failure status

  Scenario:
    Given I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch init"
      And I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch php:switch 6.6.6"
     Then I should see
        """



          [InvalidArgumentException]
          Version 6.6.6 is not installed



        php:switch [-a|--apache2] version
        php switch [-a|--apache2] version



        """
      And The command should exit with failure status

  Scenario:
    Given I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch init"
      And I have the following configuration in ".phpswitch.yml":
        """
        phpswitch:
            version: 5.3.15
        """
      And I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch php:switch off"
     Then I should see
        """
        PHP switched to system default version

        """
      And The command should exit with success status

  Scenario:
    Given I run "PHPSWITCH_PREFIX=./phpswitch bin/phpswitch init"
      And I run "source ./phpswitch/.phpswitchrc && php switch on"
     Then I should see no output
      And The command should exit with failure status
    Given I run "source ./phpswitch/.phpswitchrc && php switch off"
     Then I should see output matching
        """
        PHP switched to system default version
        PHP 5\.[0-9a-zA-Z\-\.]*.* \(cli\) \(built: .*\)
        """
      And The command should exit with success status
