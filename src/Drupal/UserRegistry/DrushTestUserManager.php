<?php

namespace Codeception\Module\Drupal\UserRegistry;

use Codeception\Exception\ConfigurationException;
use Codeception\Exception\ModuleException;
use Codeception\Lib\Console\Message;
use Codeception\Lib\Console\Output;
use Codeception\Module\DrupalUserRegistry;
use Codeception\Module\Drupal\UserRegistry\Storage\StorageInterface;
use Codeception\Util\Debug;

/**
 * Class DrushTestUserManager: create and delete test users via Drush and Drush aliases.
 *
 * @package Codeception\Module\DrupalUserRegistry
 */
class DrushTestUserManager implements TestUserManagerInterface
{
    /**
     * @var string
     *   The Drush alias on which to run user management commands. Note this is stored WITH the leading @ character.
     */
    protected $alias;

    /**
     * @var StorageInterface
     *   Storage object containing configured users/roles to manage.
     */
    protected $storage;

    /**
     * @var Output
     *   Used to print messages via Codeception's console.
     */
    protected $output;

    /**
     * @var string
     *   The drush command to use.
     */
    protected $drushCommand;

    /**
     * Constructor: ensure we have all the configuration values we need and store them.
     *
     * @param array $config
     *   The DrupalUserRegistry module configuration.
     * @param StorageInterface $storage
     *   Storage object for the list of users/roles.
     *
     * @throws ConfigurationException
     */
    public function __construct($config, StorageInterface $storage)
    {
        $this->storage = $storage;

        $this->alias = $config['drush-alias'] ?? NULL;
        $this->drushCommand = $config['drush'] ?? 'drush';

        $this->output = new Output(array());
    }

    /**
     * Gets the storage object.
     *
     * {@inheritdoc}
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Sets the storage object.
     *
     * {@inheritdoc}
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Create a test user via Drush and exec().
     *
     * {@inheritdoc}
     */
    public function createUser($user)
    {
        // Set a default email for the test user, but overwrite it if a
        // custom email address has been specified in the suite configuration.
        $email = $user->name . "@" . DrupalUserRegistry::DRUPAL_USER_EMAIL_DOMAIN;
        if (isset($user->email)) {
            $email = $user->email;
        }

        Debug::debug("Trying to create test user '{$user->name}' with email '{$email}'.");

        if ($this->userExists($user->name)) {
            if (!$user->isRoot) {
                $this->message(
                    "User '{$user->name}' already exists, skipping.",
                    new Output(array())
                )->writeln();
            }
        } else {
            // Do not try to create the root user. This should exist as UID=1.
            if ($user->isRoot) {
                $this->message(
                    "Warning: The user '{$user->name}' specified as 'root' " .
                    "does not exist. The root user should " .
                    "be the user with UID=1"
                );
                return;
            }
            // Create the user.
            $this->message("Creating test user '{$user->name}'.")->writeln();
            $this->runDrush(
                sprintf(
                    "user-create %s --mail=%s --password=%s",
                    escapeshellarg($user->name),
                    escapeshellarg($email),
                    escapeshellarg($user->pass)
                )
            );

            // Add user roles, if set.
            foreach ($user->roles as $role) {
                if ($role != "Authenticated") {
                    $this->runDrush(
                        sprintf(
                            "user-add-role %s --name=%s",
                            escapeshellarg($role),
                            escapeshellarg($user->name)
                        )
                    );
                }
            }
        }
    }

    /**
     * Create multiple test users.
     *
     * {@inheritdoc}
     */
    public function createUsers($users)
    {
        foreach ($users as $user) {
            $this->createUser($user);
        }
    }

    /**
     * Delete a test user via Drush and exec().
     *
     * {@inheritdoc}
     */
    public function deleteUser($user)
    {
        if ($user->isRoot) {
            return;
        }

        $this->message("Deleting test user '{$user->name}'.")->writeln();
        $this->runDrush(
            sprintf(
                "user-cancel %s --delete-content",
                escapeshellarg($user->name)
            )
        );
    }

    /**
     * Delete multiple test users.
     *
     * {@inheritdoc}
     */
    public function deleteUsers($users)
    {
        foreach ($users as $user) {
            $this->deleteUser($user);
        }
    }

    /**
     * Determine if a user with a given username exists.
     *
     * {@inheritdoc}
     *
     * @throws ModuleException
     */
    public function userExists($username)
    {
        $output = $this->runDrush("sqlq 'select count(uid) from users_field_data where name = \"" . $username . "\"'");

        return !($output[0] === '0');
    }

    /**
     * Run a Drush command.
     *
     * @param string $cmd
     *   The Drush command, without executable and alias, e.g. "pml". The arguments should be escaped.
     *
     * @return array
     *   Array of lines output from the Drush command.
     */
    protected function runDrush($cmd)
    {
        $cmdOutput = array();
        exec($this->prepareDrushCommand($cmd), $cmdOutput);

        return array_filter(
            $cmdOutput,
            function ($line) {
                return strpos($line, "Warning: Permanently added") !== 0;
            }
        );
    }

    /**
     * Prepare a full Drush command, to include executable and alias.
     *
     * @param string $cmd
     *   The Drush command, without executable and alias, e.g. "pml". The arguments should be escaped.
     *
     * @return string
     *   The prepared Drush command to run, complete with executable and alias.
     */
    protected function prepareDrushCommand($cmd)
    {
        $baseCmd = $this->drushCommand . " -y";
        if ($this->alias !== NULL) {
          $baseCmd .= escapeshellarg($this->alias);
        }
        $cmd = "$baseCmd $cmd";
        Debug::debug($cmd);
        return $cmd;
    }

    /**
     * @param string $text
     *   Create a new message to display.
     *
     * @return Message
     *   The new Message.
     */
    protected function message($text = '')
    {
        return new Message($text, $this->output);
    }
}
