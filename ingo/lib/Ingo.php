<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo base class.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo
{
    /**
     * String that can't be a valid folder name used to mark blacklisted email
     * as deleted.
     */
    const BLACKLIST_MARKER = '++DELETE++';

    /**
     * Define the key to use to indicate a user-defined header is requested.
     */
    const USER_HEADER = '++USER_HEADER++';

    /**
     * Only filter unseen messages.
     */
    const FILTER_UNSEEN = 1;

    /**
     * Only filter seen messages.
     */
    const FILTER_SEEN = 2;

    /**
     * Constants for rule types.
     */
    const RULE_ALL = 0;
    const RULE_FILTER = 1;
    const RULE_BLACKLIST = 2;
    const RULE_WHITELIST = 3;
    const RULE_VACATION = 4;
    const RULE_FORWARD = 5;
    const RULE_SPAM = 6;

    /**
     * hasSharePermission() cache.
     *
     * @var integer
     */
    static private $_shareCache = null;

    /**
     * Generates a folder widget.
     *
     * If an application is available that provides a mailboxList method
     * then a &lt;select&gt; input is created. Otherwise a simple text field
     * is returned.
     *
     * @param string $value    The current value for the field.
     * @param string $tagname  The label for the select tag.
     *
     * @return string  The HTML to render the field.
     */
    static public function flistSelect($value = null, $tagname = 'actionvalue')
    {
        global $page_output, $registry;

        if ($registry->hasMethod('mail/mailboxList')) {
            try {
                $mailboxes = $registry->call('mail/mailboxList');

                $text = '<select class="flistSelect" id="' . $tagname . '" name="' . $tagname . '">' .
                    '<option value="">' . _("Select target folder:") . '</option>' .
                    '<option disabled="disabled">- - - - - - - - - -</option>';

                if ($registry->hasMethod('mail/createMailbox')) {
                    $text .= '<option class="flistCreate" value="">' . _("Create new folder") . '</option>' .
                        '<option disabled="disabled">- - - - - - - - - -</option>';
                }

                foreach ($mailboxes as $val) {
                    $text .= sprintf(
                        "<option value=\"%s\"%s>%s</option>\n",
                        htmlspecialchars($val['ob']->utf7imap),
                        ($val['ob']->utf7imap === $value) ? ' selected="selected"' : '',
                        str_repeat('&nbsp;', $val['level'] * 2) . htmlspecialchars($val['label'])
                    );
                }

                $page_output->addScriptFile('new_folder.js');
                $page_output->addInlineJsVars(array(
                    'IngoNewFolder.folderprompt' => _("Please enter the name of the new folder:")
                ));

                return $text . '</select>';
            } catch (Horde_Exception $e) {}
        }

        return '<input id="' . $tagname . '" name="' . $tagname . '" size="40" value="' . $value . '" />';
    }

    /**
     * Validates an IMAP mailbox provided by user input.
     *
     * @param Horde_Variables $vars  An variables object.
     * @param string $name           The form name of the folder input.
     *
     * @return string  The IMAP mailbox name.
     * @throws Horde_Exception
     */
    static public function validateFolder(Horde_Variables $vars, $name)
    {
        $new_id = $name . '_new';

        if (isset($vars->$new_id)) {
            if ($GLOBALS['registry']->hasMethod('mail/createMailbox') &&
                $GLOBALS['registry']->call('mail/createMailbox', array($vars->$new_id))) {
                return $vars->$new_id;
            }
        } elseif (isset($vars->$name) && strlen($vars->$name)) {
            return $vars->$name;
        }

        throw new Ingo_Exception(_("Could not validate IMAP mailbox."));
    }

    /**
     * Returns the user whose rules are currently being edited.
     *
     * @param boolean $full  Always return the full user name with realm?
     *
     * @return string  The current user.
     */
    static public function getUser($full = true)
    {
        if (empty($GLOBALS['ingo_shares'])) {
            return $GLOBALS['registry']->getAuth($full ? null : 'bare');
        }

        list(, $user) = explode(':', $GLOBALS['session']->get('ingo', 'current_share'), 2);
        return $user;
    }

    /**
     * Returns the domain name, if any of the user whose rules are currently
     * being edited.
     *
     * @return string  The current user's domain name.
     */
    static public function getDomain()
    {
        $user = self::getUser(true);
        $pos = strpos($user, '@');

        return ($pos !== false)
            ? substr($user, $pos + 1)
            : false;
    }

    /**
     * Connects to the backend, uploads the scripts and sets them active.
     *
     * @param array $scripts       A list of scripts to set active.
     * @param boolean $deactivate  If true, notification will identify the
     *                             script as deactivated instead of activated.
     *
     * @throws Ingo_Exception
     */
    static public function activateScripts($scripts, $deactivate = false)
    {
        foreach ($scripts as $script) {
            try {
                $GLOBALS['injector']
                    ->getInstance('Ingo_Factory_Transport')
                    ->create($script['transport'])
                  ->setScriptActive($script);
            } catch (Ingo_Exception $e) {
                $msg = $deactivate
                  ? _("There was an error deactivating the script.")
                  : _("There was an error activating the script.");
                throw new Ingo_Exception(sprintf(_("%s The driver said: %s"), $msg, $e->getMessage()));
            }
        }

        $msg = ($deactivate)
            ? _("Script successfully deactivated.")
            : _("Script successfully activated.");
        $GLOBALS['notification']->push($msg, 'horde.success');
    }

    /**
     * Does all the work in updating the script on the server.
     *
     * @throws Ingo_Exception
     */
    static public function updateScript()
    {
        foreach ($GLOBALS['injector']->getInstance('Ingo_Factory_Script')->createAll() as $script) {
            if ($script->hasFeature('script_file')) {
                try {
                    /* Generate and activate the script. */
                    self::activateScripts($script->generate());
                } catch (Ingo_Exception $e) {
                    throw new Ingo_Exception(sprintf(_("Script not updated: %s"), $e->getMessage()));
                }
            }
        }
    }

    /**
     * Determine the backend to use.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' either field
     * in the backend's definition.  The 'preferred' field may take a
     * single value or an array of multiple values.
     *
     * @return array  The backend entry.
     * @throws Ingo_Exception
     */
    static public function getBackend()
    {
        $backends = Horde::loadConfiguration('backends.php', 'backends', 'ingo');
        if (!isset($backends) || !is_array($backends)) {
            throw new Ingo_Exception(_("No backends configured in backends.php"));
        }

        $backend = null;
        foreach ($backends as $name => $temp) {
            if (!empty($temp['disabled'])) {
                continue;
            }
            if (!isset($backend)) {
                $backend = $name;
            } elseif (!empty($temp['preferred'])) {
                if (is_array($temp['preferred'])) {
                    foreach ($temp['preferred'] as $val) {
                        if (($val == $_SERVER['SERVER_NAME']) ||
                            ($val == $_SERVER['HTTP_HOST'])) {
                            $backend = $name;
                        }
                    }
                } elseif (($temp['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($temp['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backend = $name;
                }
            }
            $backends[$name]['id'] = $name;
        }

        /* Check for valid backend configuration. */
        if (is_null($backend)) {
            throw new Ingo_Exception(_("No backend configured for this host"));
        }

        $backend = $backends[$backend];

        foreach (array('script', 'transport') as $val) {
            if (empty($backend[$val])) {
                throw new Ingo_Exception(sprintf(_("No \"%s\" element found in backend configuration."), $val));
            }
        }

        return $backend;
    }

    /**
     * Returns all rulesets a user has access to, according to several
     * parameters/permission levels.
     *
     * @param boolean $owneronly   Only return rulesets that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter rulesets by.
     *
     * @return array  The ruleset list.
     */
    static public function listRulesets($owneronly = false,
                                        $permission = Horde_Perms::SHOW)
    {
        try {
            $tmp = $GLOBALS['ingo_shares']->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm' => $permission,
                      'attributes' => $owneronly ? $GLOBALS['registry']->getAuth() : null));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array();
        }

        /* Check if filter backend of the share still exists. */
        $backends = Horde::loadConfiguration('backends.php', 'backends', 'ingo');
        if (!isset($backends) || !is_array($backends)) {
            throw new Ingo_Exception(_("No backends configured in backends.php"));
        }
        $rulesets = array();
        foreach ($tmp as $id => $ruleset) {
            list($backend) = explode(':', $id);
            if (isset($backends[$backend]) &&
                empty($backends[$backend]['disabled'])) {
                $rulesets[$id] = $ruleset;
            }
        }

        return $rulesets;
    }

    /**
     * TODO
     */
    static public function hasSharePermission($mask = null)
    {
        if (!isset($GLOBALS['ingo_shares'])) {
            return true;
        }

        if (is_null(self::$_shareCache)) {
            self::$_shareCache = $GLOBALS['ingo_shares']->getPermissions($GLOBALS['session']->get('ingo', 'current_share'), $GLOBALS['registry']->getAuth());
        }

        return self::$_shareCache & $mask;
    }

    /**
     * Returns the vacation reason with all placeholder replaced.
     *
     * @param string $reason  The vacation reason including placeholders.
     * @param integer $start  The vacation start timestamp.
     * @param integer $end    The vacation end timestamp.
     *
     * @return string  The vacation reason suitable for usage in the filter
     *                 scripts.
     */
    static public function getReason($reason, $start, $end)
    {
        $identity = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create(Ingo::getUser());
        $format = $GLOBALS['prefs']->getValue('date_format');

        return str_replace(array('%NAME%',
                                 '%EMAIL%',
                                 '%SIGNATURE%',
                                 '%STARTDATE%',
                                 '%ENDDATE%'),
                           array($identity->getName(),
                                 $identity->getDefaultFromAddress(),
                                 $identity->getValue('signature'),
                                 $start ? strftime($format, $start) : '',
                                 $end ? strftime($format, $end) : ''),
                           $reason);
    }

    /**
     * Updates a list (blacklist/whitelist) filter.
     *
     * @param mixed $addresses  Addresses of the filter.
     * @param integer $type     Type of filter.
     *
     * @return Horde_Storage_Rule  The filter object.
     */
    static public function updateListFilter($addresses, $type)
    {
        global $injector;

        $storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $rule = $storage->retrieve($type);

        switch ($type) {
        case $storage::ACTION_BLACKLIST:
            $rule->setBlacklist($addresses);
            $addr = $rule->getBlacklist();

            $rule2 = $storage->retrieve($storage::ACTION_WHITELIST);
            $addr2 = $rule2->getWhitelist();
            break;

        case $storage::ACTION_WHITELIST:
            $rule->setWhitelist($addresses);
            $addr = $rule->getWhitelist();

            $rule2 = $storage->retrieve($storage::ACTION_BLACKLIST);
            $addr2 = $rule2->getBlacklist();
            break;
        }

        /* Filter out the rule's addresses in the opposite filter. */
        $ob = new Horde_Mail_Rfc822_List($addr2);
        $ob->setIteratorFilter(0, $addr);

        switch ($type) {
        case $storage::ACTION_BLACKLIST:
            $rule2->setWhitelist($ob->bare_addresses);
            break;

        case $storage::ACTION_WHITELIST:
            $rule2->setBlacklist($ob->bare_addresses);
            break;
        }

        $storage->store($rule);
        $storage->store($rule2);

        return $rule;
    }

    /**
     * Output description for a rule.
     *
     * @param array $rule  Rule.
     *
     * @return string  Text description.
     */
    static public function ruleDescription($rule)
    {
        global $injector;

        $condition_size = count($rule['conditions']) - 1;
        $descrip = '';
        $storage = $injector->getInstance('Ingo_Factory_Storage')->create();

        foreach ($rule['conditions'] as $key => $val) {
            $info = $storage->getTestInfo($val['match']);
            $descrip .= sprintf("%s %s \"%s\"", _($val['field']), $info->label, $val['value']);

            if (!empty($val['case'])) {
                $descrip .= ' [' . _("Case Sensitive") . ']';
            }

            if ($key < $condition_size) {
                $descrip .= ($rule['combine'] == Ingo_Storage::COMBINE_ALL)
                    ? _(" and")
                    : _(" or");
                $descrip .= "\n  ";
            }
        }

        $descrip .= "\n" . $storage->getActionInfo($rule['action'])->label;

        if ($rule['action-value']) {
            $descrip .= ': ' . $rule['action-value'];
        }

        if ($rule['stop']) {
            $descrip .= "\n[stop]";
        }

        return $descrip;
    }

}
