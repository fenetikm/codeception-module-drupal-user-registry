<?php

use \Codeception\Module\Drupal\UserRegistry\DrupalTestUser;
use \Codeception\Module\DrupalUserRegistry;
use \Codeception\Util\Fixtures;

/**
 * Unit tests for the 'public API' methods of the DrupalUserRegistry class.
 *
 * This class only contains tests which cover API methods, i.e. those methods which are composed into the Actor class
 * and available via `$I`. Other, non-API related tests are included in DrupalUserRegistryTest.php
 */
class DrupalUserRegistryApiTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     *   Store the Actor object being used to test.
     */
    protected $tester;

    /**
     * @var \Codeception\Module\DrupalUserRegistry
     *   Store any instance of the module being tested.
     */
    protected $module;

    /**
     * Don't use _before() as not all the tests require this setup.
     *
     * Note this function can't be called setUp()...
     */
    protected function initialise()
    {
        $this->module = new DrupalUserRegistry();
        $this->module->_setConfig(Fixtures::get("validModuleConfig"));
        $this->module->_initialize();
    }

    /**
     * Test getRootUser()
     *
     * @group api
     *
     * @throws \Codeception\Exception\Module
     */
    public function testGetRootUser()
    {
        $this->initialise();
        $rootUser = $this->module->getRootUser();

        $this->tester->amGoingTo("check the returned data is as expected");
        $this->assertEquals("test.administrator", $rootUser->name, "Usernames did not match.");
        $this->assertEquals("foo", $rootUser->pass, "Passwords did not match.");
        $this->assertTrue($rootUser->isRoot, "root user is not flagged as being root");
    }

    /**
     * Test getRootUser() returns false when there is no root user configured.
     *
     * @group api
     */
    public function testGetRootUserReturnsFalseWhenNotConfigured()
    {
        // Grab a valid module configuration but remove any configured root user.
        $config = Fixtures::get("validModuleConfig");
        unset($config["users"]["administrator"]["root"]);

        $this->module = new DrupalUserRegistry();
        $this->module->_setConfig($config);
        $this->module->_initialize();
        $this->assertFalse($this->module->getRootUser(), "getRootUser() did not return false");
    }

    /**
     * Test getUser()
     *
     * @group api
     */
    public function testGetUser()
    {
        $this->initialise();

        $expected = new DrupalTestUser("test.administrator", "foo", array("administrator"));
        $this->assertTestUsersEqual($expected, $this->module->getUser("test.administrator"));

        $this->assertFalse(
            $this->module->getUser("invalid.test.user"),
            "Result from getUser() was not false when requesting an invalid test user."
        );
    }

    /**
     * Test getUserByRole()
     *
     * @group api
     */
    public function testGetUserByRole()
    {
        $this->initialise();
        $expected = new DrupalTestUser("test.administrator", "foo", array("administrator", "editor"));
        $this->assertTestUsersEqual($expected, $this->module->getUserByRole(array("administrator", "editor")));
    }

    /**
     * Test getUserByRole() - it should not return the DrupalTestUser if the exact roles are not specified.
     *
     * @group api
     */
    public function testGetUserByRoleDoesNotReturnUserWhenOnlySomeRolesMatch()
    {
        $this->initialise();
        $this->assertFalse($this->module->getUserByRole("administrator"));
    }

    /**
     * Test getUserByRole() - it should not return the DrupalTestUser if the exact roles are not specified.
     *
     * @group api
     */
    public function testGetUserByRoleDoesNotReturnUserWhenUserDoesNotHaveAllRolesSpecified()
    {
        $this->initialise();
        $this->assertFalse($this->module->getUserByRole(array("administrator", "editor", "moderator")));
    }

    /**
     * Test getRoles()
     *
     * @group api
     */
    public function testGetRoles()
    {
        $this->initialise();
        $expected = ["administrator", "editor", "moderator"];
        $this->assertEquals($expected, $this->module->getRoles());

    }

    /**
     * Expect to see getLoggedInUser return null before a logged in user is set.
     *
     * @group api
     */
    public function testGetLoggedInUserIsNullBeforeAnyUserIsSet()
    {
        $this->initialise();
        $loggedInUser = $this->module->getLoggedInUser();
        $this->assertNull(
            $loggedInUser,
            "getLoggedInUser() returned something other than null before setLoggedInUser() was called."
        );
    }

    /**
     * Sequential test for the three 'logged in user' helper methods.
     *
     * Test that the result from getLoggedInUser() is what we expect after setting it with setLoggedInUser(), then
     * returns null after calling removeLoggedInUser()
     *
     * @group api
     */
    public function testSetGetRemoveLoggedInUserHelpers()
    {
        $this->initialise();

        // Call set with expected test values.
        $testUser = Fixtures::get("drupalTestUser");
        $this->module->setLoggedInUser($testUser);
        // @todo verify set (WITHOUT calling get?)

        $loggedInUser = $this->module->getLoggedInUser();

        // Check the returned data is as expected.
        $this->assertTestUsersEqual($testUser, $loggedInUser);

        // Remove logged in user and ensure is now null.
        $this->module->removeLoggedInUser();
        $this->assertNull($this->module->getLoggedInUser());
    }

    /**
     * Test that the returned result from getLoggedInUser() is null after calling removeLoggedInUser()
     *
     * Note that this member variable won't be initialised during this test, so it will be null regardless. How to
     * verify this method works in isolation? This test at least verifies that removeLoggedInUser() doesn't actually
     * set the logged in user to anything other than null.
     *
     * @group api
     */
    public function testRemoveLoggedInUser()
    {
        $this->initialise();
        $this->module->removeLoggedInUser();
        $this->assertNull($this->module->getLoggedInUser(), "Value returned form getLoggedInUser() was not null.");
    }

    /**
     * Helper when asserting DrupalTestUser objects are equal.
     *
     * @todo This is here because of issues using assertEquals() on objects. Needs looking into.
     *
     * @param DrupalTestUser $expected
     *   The expected test user.
     * @param mixed $actual
     *   The actual "test user" returned during the test.
     * @param bool $checkRole
     *   When true, the test users' roles will also be compared.
     */
    protected function assertTestUsersEqual(DrupalTestUser $expected, $actual, $checkRole = false)
    {
        $this->assertEquals($expected->name, $actual->name, "Usernames did not match.");
        $this->assertEquals($expected->pass, $actual->pass, "Passwords did not match.");

        if ($checkRole) {
            $this->assertEquals($expected->roleName, $actual->roleName, "Role names did not match.");
        }
    }
}
