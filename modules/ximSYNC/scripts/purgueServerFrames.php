<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
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


// for legacy compatibility
if (!defined('XIMDEX_ROOT_PATH')) {
    require_once dirname(__FILE__) . '/../../../bootstrap.php';
}



/*
*	Purgue files from data/sync/serverframes directory
*
* 	Launch as follow: <ximDEX_HOME>$ php bootstrap.php modules/ximSYNC/scripts/purgueServerFrames.php
*
*/

//
\Ximdex\Modules\Manager::file('/inc/model/ServerFrame.class.php', 'ximSYNC');

$serverFrame = new ServerFrame();

$outdatedServerFrames = $serverFrame->find('IdSync', "State IN ('Canceled', 'Removed', 'Replaced', 'Out')", NULL, MONO);

if (sizeof($outdatedServerFrames) > 0) {
	foreach ($outdatedServerFrames as $serverFrameId) {
		$serverFrame = new ServerFrame($serverFrameId);
		$serverFrame->deleteSyncFile();
	}
}

?>