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
use Ximdex\Models\Language;
use Ximdex\Models\Node;
use Ximdex\MVC\ActionAbstract;

class Action_modifylanguage extends ActionAbstract
{
    /**
     * Main method: shows initial form
     */
    public function index()
    {
    	$idNode = (int) $this->request->getParam('nodeid');
		$node = new Node($idNode);
		$language = new Language($idNode);
		if (! $language->get('IdLanguage') or ! $node->get('IdNode')) {
			$this->messages->add(_('Language could not be successfully loaded, contact with your administrator'), MSG_TYPE_ERROR);
			Logger::error('Language not loaded: id=' . $idNode);
			$this->render(array('messages' => $this->messages->messages), null, 'messages.tpl');
			return;
		}
		$values = array(
			'iso_name' => $language->get('IsoName'),
			'name' => $language->get('Name'),
			'enabled' => $language->get('Enabled'),
			'description' => $node->get('Description'),
            'nodeTypeID' => $node->nodeType->getID(),
            'node_Type' => $node->nodeType->getName(),
			'go_method' => 'modifylanguage'
		);
		$this->render($values, null, 'default-3.0.tpl');
    }

    public function modifylanguage()
    {
    	$id = (int) $this->request->getParam('nodeid');
    	$success = false;
    	$node = new Node($id);
        if ($node->isValidName($this->request->getParam('Name'), $node->get('IdNodeType'))) {
            $language = new Language($id);
            $language->set('Enabled', $this->request->getParam('enabled') ? 1 : 0);
            $success = $language->update();
            if ($success === false) {
                $this->messages->mergeMessages($language->messages);
            } else {
                $node->set('Description', $this->request->getParam('Description'));
                $node->set('Name', $this->request->getParam('Name'));
                $success = $node->update();
                if ($success === false) {
                    $this->messages->mergeMessages($node->messages);
                }
            }
            if ($success) {
                $this->messages->add(_('Language has been successfully updated'), MSG_TYPE_NOTICE);
            } else {
                if (! $this->messages->count()) {
                    $this->messages->add(_('An error occurred while updating language'), MSG_TYPE_ERROR);
                }
            }
        } else {
            $this->messages->add(_('Language name is not valid'), MSG_TYPE_WARNING);
        }
		$values = array('goback' => true, 'messages' => $this->messages->messages, 'parentID' => $node->get('IdParent'));
        $this->sendJSON($values);
    }
}
