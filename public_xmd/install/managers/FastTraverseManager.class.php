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

use Ximdex\Models\Node;

require_once APP_ROOT_PATH . '/install/managers/InstallManager.class.php';

class FastTraverseManager extends InstallManager
{
	/**
	 * Build FastTraverse and full path to every node in Ximdex
	 * 
	 * @throws Exception
	 * @return boolean
	 */
	public function buildFastTraverse()
	{
		$this->deleteFastTraverse();
		$node = new Node();
		$results = $node->find('IdNode', '', array(), MONO);
		$dbUpdate = new \Ximdex\Runtime\Db();
		foreach ($results as $i => $idNode) {
			$node = new Node($idNode);
			$node->updateFastTraverse(false);
			$path = pathinfo($node->getPath());
			if (! isset($path['dirname'])) {
				$path['dirname'] = '/' ;
			}
			$this->installMessages->printIteration($i);
			$res = $dbUpdate->execute(sprintf("update Nodes set Path = '%s' where idnode = %s", $path['dirname'], $idNode));
			if ($res === false) {
			    throw new Exception('Cannot generate the fast traverse data and nodes path');
			}
		}
        return true;
	}

	/**
	 * Empty fast traverse table in DB
	 */
	private function deleteFastTraverse()
	{
		$sql = 'DELETE FROM FastTraverse';
		$db = new \Ximdex\Runtime\Db();
		$db->execute($sql);
	}
}
