<?php

/**
 *  \details &copy; 2018 Open Ximdex Evolution SL [http://www.ximdex.org]
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
use Ximdex\Models\NodeAllowedContent;
use Ximdex\MVC\ActionAbstract;

Ximdex\Modules\Manager::file('/actions/copy/baseIO.php');

class Action_copy extends ActionAbstract
{
    /**
     * Main method
     */
    public function index()
    {
        $node = new Node($this->request->getParam('nodeid'));
        if (! $node->get('IdNode')) {
            $this->messages->add(_('Error with parameters'), MSG_TYPE_ERROR);
            $values = array('messages' => $this->messages->messages);
            $this->renderMessages();
        }
        else {
            $targetNodes = $this->getTargetNodes($node->GetID(), $node->GetNodeType());
            /*
            $targetNodes = array_filter($targetNodes, function($nodes) use ($node) {
                return $nodes['idnode'] != $node->GetID();
            });
            */
            $values = array(
                'id_node' => $node->get('IdNode'),
                'nodetypeid' => $node->nodeType->get('IdNodeType'),
                'filtertype' => $node->nodeType->get('Name'),
                'targetNodes' => $targetNodes,
                'node_path' => $node->GetPath(),
                'go_method' => 'copyNodes',
                'nodeTypeID' => $node->nodeType->getID(),
                'node_Type' => $node->nodeType->GetName(),
                'name' => $node->GetNodeName()
            );
            $this->addCss('/actions/copy/resources/css/style.css');
            $this->render($values, NULL, 'default-3.0.tpl');
        }
    }

    public function copyNodes()
    {
        // Extracts info of actual node which the action is executed
        $nodeID = $this->request->getParam('nodeid');
        $destIdNode = $this->request->getParam('targetid');
        $recursive = $this->request->getParam('recursive');
        $recursive = $recursive == 'on' ? true : false;
        if ($nodeID == $destIdNode) {
            $this->messages->add(_('Source node cannot be the same as destination node'), MSG_TYPE_ERROR);
            $this->sendJSON(array('messages' => $this->messages->messages));
            return;
        }
        $this->messages = copyNode($nodeID, $destIdNode, $recursive);
        $values = array('messages' => $this->messages->messages,
            'parentID' => $destIdNode,
            'action_with_no_return' => true);
        $this->sendJSON($values);
    }

    /**
     * Get an array with the available target info
     * 
     * @param int $idNode of the node to move.
     * @param int $idNodeType of the node to move.
     * @return array With path and idnode for every target folder
     */
    protected function getTargetNodes(int $idNode, int $idNodeType) : array
    {
        $nodeAllowedContent = new NodeAllowedContent();
        $arrayNodeTypesAllowed = $nodeAllowedContent->getAllowedParents($idNodeType);
        $node = new Node($idNode);
        $arrayIdnodes = $node->find('IdNode', 'idnodetype in (' . implode(',', $arrayNodeTypesAllowed) . ')', null, MONO, true, null, 'Path, Name');
        $targetNodes = [];
        foreach ($arrayIdnodes as $idCandidateNode) {
            if ($idCandidateNode == $idNode) {
                continue;
            }
            if ($this->checkTargetConditions($idNode, $idCandidateNode)) {
                $targetNode =  new Node($idCandidateNode);
                $targetNodes[] = ['path' => str_replace('/Ximdex/Projects/', '', $targetNode->GetPath()), 'idnode' => $targetNode->GetID()];
            }
        }
        return $targetNodes;
    }

    /**
     * Check if the propousal node can be target for the current one.
     * Must be in the same project
     * 
     * @param int $idCurrentNode
     * @param int $idCandidateNode
     * @result boolean True if everything is ok.
     */
    protected function checkTargetConditions(int $idCurrentNode, int $idCandidateNode) : bool
    {
        $node = new Node($idCurrentNode);
        $candidateNode = new Node($idCandidateNode);
        return $node->getProject() == $candidateNode->getProject();
    }
}
