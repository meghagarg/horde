<?php
/**
 * Horde_Form for editing task lists.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Nag
 */
/**
 * The Nag_EditTaskListForm class provides the form for
 * editing a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_Form_EditTaskList extends Horde_Form
{
    /**
     * Task list being edited
     *
     *
     * @var Horde_Share_Object
     */
    protected $_tasklist;

    /**
     *
     * @param array $vars
     * @param Horde_Share_Object $tasklist
     */
    public function __construct($vars, Horde_Share_Object $tasklist)
    {
        $this->_tasklist = $tasklist;

        $owner = $tasklist->get('owner') == $GLOBALS['registry']->getAuth() ||
            (is_null($tasklist->get('owner')) &&
             $GLOBALS['registry']->isAdmin());

        parent::__construct(
            $vars,
            $owner
                ? sprintf(_("Edit %s"), $tasklist->get('name'))
                : $tasklist->get('name')
        );

        $this->addHidden('', 't', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);

        if (!$owner) {
            $v = $this->addVariable(_("Owner"), 'owner', 'text', false);
            $owner_name = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create($tasklist->get('owner'))
                ->getValue('fullname');
            if (trim($owner_name) == '') {
                $owner_name = $tasklist->get('owner');
            }
            $v->setDefault($owner_name ? $owner_name : _("System"));
        }

        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        if ($GLOBALS['registry']->isAdmin()) {
            $this->addVariable(
                _("System Task List"), 'system', 'boolean', false, false,
                _("System task lists don't have an owner. Only administrators can change the task list settings and permissions.")
            );
        }
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        /* Display URL. */
        $url = Horde::url('list.php', true, -1)
            ->add('display_tasklist', $tasklist->getName());
        $this->addVariable(
             _("Display URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $url,
                 'text' => $url,
                 'title' => _("Click or copy this URL to display this task list"),
                 'target' => '_blank')
             )
        );

        /* Subscription URLs. */
        $url = $GLOBALS['registry']->get('webroot', 'horde');
        if (isset($GLOBALS['conf']['urls']['pretty']) &&
            $GLOBALS['conf']['urls']['pretty'] == 'rewrite') {
            $webdavUrl = $url . '/rpc/nag/';
            $caldavUrl = $url . '/rpc/calendars/';
            $accountUrl = $url . '/rpc/';
        } else {
            $webdavUrl = $url . '/rpc.php/nag/';
            $caldavUrl = $url . '/rpc.php/calendars/';
            $accountUrl = $url . '/rpc.php/';
        }
        $accountUrl = Horde::url($accountUrl, true, -1)
            . 'principals/'. $GLOBALS['registry']->getAuth() . '/';
        $caldavUrl = Horde::url($caldavUrl, true, -1)
            . ($tasklist->get('owner')
               ? $tasklist->get('owner')
               : '-system-')
                . '/'
            . $GLOBALS['injector']->getInstance('Horde_Dav_Storage')->getExternalCollectionId($tasklist->getName(), 'tasks')
            . '/';
        $this->addVariable(
             _("CalDAV Subscription URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $caldavUrl,
                 'text' => $caldavUrl,
             'title' => _("Copy this URL to a CalDAV client to subscribe to this task list"),
                 'target' => '_blank')
             )
        );
        $this->addVariable(
             _("CalDAV Account URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $accountUrl,
                 'text' => $accountUrl,
             'title' => _("Copy this URL to a CalDAV client to subscribe to all your task lists"),
                 'target' => '_blank')
             )
        );
        $webdavUrl = Horde::url($webdavUrl, true, -1)
            . ($tasklist->get('owner')
               ? $tasklist->get('owner')
               : '-system-')
            . '/' . $tasklist->getName() . '.ics';
        $this->addVariable(
             _("WebDAV/ICS Subscription URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $webdavUrl,
                 'text' => $webdavUrl,
                 'title' => _("Copy this URL to a WebDAV or ICS client to subscribe to this task list"),
                 'target' => '_blank')
             )
        );

        /* Permissions link. */
        if (empty($GLOBALS['conf']['share']['no_sharing']) && $owner) {
            $url = Horde::url($GLOBALS['registry']->get('webroot', 'horde')
                              . '/services/shares/edit.php')
                ->add(array('app' => 'nag', 'share' => $tasklist->getName()));
            $this->addVariable(
                 '', '', 'link', false, false, null,
                 array(array(
                     'url' => $url,
                     'text' => _("Change Permissions"),
                     'onclick' => Horde::popupJs(
                          $url,
                          array('params' => array('urlencode' => true)))
                          . 'return false;',
                     'class' => 'horde-button',
                     'target' => '_blank')
                 )
            );
        }

        $this->setButtons(array(
            _("Save"),
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel"))
        ));
    }

    public function execute()
    {
        switch ($this->_vars->submitbutton) {
        case _("Save"):
            $info = array();
            foreach (array('name', 'color', 'description', 'system') as $key) {
                $info[$key] = $this->_vars->get($key);
            }
            Nag::updateTasklist($this->_tasklist, $info);
            break;
        case _("Delete"):
            Horde::url('tasklists/delete.php')
                ->add('t', $this->_vars->t)
                ->redirect();
            break;
        case _("Cancel"):
            Horde::url('list.php', true)->redirect();
            break;
        }
    }
}
