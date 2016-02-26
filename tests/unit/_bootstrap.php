<?php
/**
 * Here you can initialize variables that will be available to your tests.
 */

use \Codeception\Util\Fixtures;
use \Codeception\Module\Drupal\UserRegistry\DrupalTestUser;

// Define a complete, valid configuration identical to that configured in the functional suite.
$mockValidModuleConfig = array(
    "create" => false,
    "delete" => false,
    "users" => array(
        "administrator" => array(
            "name" => "test.administrator",
            "email" => "test.administrator@example.com",
            "pass" => "foo",
            "roles" => array("administrator", "editor"),
            "root" => true,
        ),
        "editor" => array(
            "name" => "test.editor",
            "email" => "test.editor@example.com",
            "pass" => "foo",
            "roles" => array("editor", "moderator"),
        ),
        "moderator" => array(
            "name" => "test.moderator",
            "email" => "test.moderator@example.com",
            "pass" => "foo",
            "roles" => array("moderator"),
        ),
    ),
    "drush-alias" => "@d7.local",
);

// Define a configuration which is invalid solely because the drush-alias entry is missing.
$mockInvalidModuleConfig = $mockValidModuleConfig;
unset($mockInvalidModuleConfig["drush-alias"]);

// Define a complete, valid configuration with a custom username prefix.
$mockCustomPrefixModuleConfig = $mockValidModuleConfig;
$mockCustomPrefixModuleConfig["username-prefix"] = "custom";

// Define a complete configuration with an invalid custom username prefix.
$mockInvalidCustomPrefixModuleConfig = $mockValidModuleConfig;
$mockInvalidCustomPrefixModuleConfig["username-prefix"] = "xyz";

Fixtures::add("validModuleConfig", $mockValidModuleConfig);
Fixtures::add("invalidModuleConfig", $mockInvalidModuleConfig);
Fixtures::add("customPrefixModuleConfig", $mockCustomPrefixModuleConfig);
Fixtures::add("invalidCustomPrefixModuleConfig", $mockInvalidCustomPrefixModuleConfig);

// Define a mock test user.
$drupalTestUser = new DrupalTestUser("test.mock.user", "password", "mock");
Fixtures::add("drupalTestUser", $drupalTestUser);
