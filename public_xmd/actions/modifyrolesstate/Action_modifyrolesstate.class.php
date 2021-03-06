<?php
/**
 *  \details &copy; 2019 Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */

use Ximdex\Logger;
use Ximdex\Models\Role;
use Ximdex\MVC\ActionAbstract;
use Ximdex\Runtime\App;

/**
 * @deprecated
 */
class Action_modifyrolesstate extends ActionAbstract
{
    /**
     * Main method: shows initial form
     */
    public function index()
    {
    	$idNode = $this->request->getParam('state');
    	$query = sprintf('SELECT r.IdRole, r.Name'
    			. ' FROM Roles r'
    			. ' INNER JOIN RelRolesStates rrs ON r.IdRole = rrs.IdRole AND rrs.IdState = %d', $idNode);
    	$dbObj = new \Ximdex\Runtime\Db();
    	$dbObj->query($query);
    	$rolesStates = array();
	    $asociatedRoles = array();
        $query = App::get('\Ximdex\Utils\QueryManager');
        $action = $query->getPage();
        $actionAdd = $action . $query->buildWith(array('method' => 'addrolestate'));
        $actionDelete = $action . $query->buildWith(array('method' => 'deleterolestate'));
    	while (! $dbObj->EOF) {
    		$rolesStates[] = array(	'Name' => $dbObj->getValue('Name'), 'IdRole' => $dbObj->getValue('IdRole'));
    		$asociatedRoles[] = $dbObj->getValue('IdRole');
    		$dbObj->next();
    	}
    	$role = new Role();
    	$allRoles = $role->find('IdRole, Name');
    	foreach ($allRoles as $key => $roleInfo) {
		if ($asociatedRoles) {
    			if (in_array($roleInfo['IdRole'], $asociatedRoles)) {
    				unset($allRoles[$key]);
    			}
		    }
    	}
	    $values = array(
			'id_node' => $idNode,
			'applied_roles' => $rolesStates,
			'all_roles' => $allRoles,
			'action_add' => $actionAdd,
			'action_delete' => $actionDelete,
			);
	    $this->render($values, null, 'default-3.0.tpl');
    }

    public function addrolestate()
    {
    	$idNode = $this->request->getParam('state');
    	$idRole = $this->request->getParam('id_role');
		$role = new Role($idRole);
		if (! $role->get('IdRole')) {
			$this->messages->add(_('Error: Role which you want to associate with workflow status could not be found'), MSG_TYPE_ERROR);
			Logger::error('IdRole ' . $idRole . ' not found for action modifyrolestate'); 
			$this->render(array('messages' => $this->messages->messages), '', 'messages.tpl');
		}
		$role->AddState($idNode);
		$this->messages->add(_('Role has been successfully associated with workflow status'), MSG_TYPE_NOTICE);
		$this->render(array('messages' => $this->messages->messages), '', 'messages.tpl');
    }

    public function deleterolestate()
    {
    	$idNode = $this->request->getParam('state');
    	$rolesToDelete = $this->request->getParam('roles_to_delete');
    	if (! (is_array($rolesToDelete) || !empty($rolesToDelete))) {
    		$this->messages->add(_('Any role has been selected to be deleted'), MSG_TYPE_WARNING);
    		$this->render(array('messages' => $this->messages->messages), '', 'messages.tpl');
    		return ;
    	}
    	foreach (array_keys($rolesToDelete) as $idRole) {
    		$role = new Role($idRole);
    		$role->deleteState($idNode);
    	}
    	$this->redirectTo('index');
    }
}
