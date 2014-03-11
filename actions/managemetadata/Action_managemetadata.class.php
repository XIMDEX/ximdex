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



ModulesManager::file('/inc/metadata/MetadataManager.class.php');
ModulesManager::file('/inc/parsers/ParsingRng.class.php');



/**
 * Manage metadata action. 
 *
 */
class Action_managemetadata extends ActionAbstract {

	/**
	 * Main function
	 *
	 * Load the manage metadata form.
	 *
	 * Request params:
	 * 
	 * * nodeid
	 * 
	 */
	public function index() {

		//Load css and js resources for action form.
		$this->addCss('/actions/managemetadata/resources/css/style.css');
		$this->addJs('/actions/managemetadata/resources/js/index.js');

		$nodeId = $this->request->getParam('nodeid');
		$mm = new MetadataManager($nodeId);

		$node = new Node($nodeId);
		$info = $node->loadData();
		$values["nodename"] = $info["name"];
		$values["nodeversion"] = $info["version"].".".$info["subversion"];
		$values["nodepath"] = $info["path"];
		$values["typename"] = $info["typename"];

		$values["elements"] = array();
		
		$nodesearch = new Node();
		$idRelaxNGNode = $mm->getMetadataSchema();
		if ($idRelaxNGNode) {
			$rngParser = new ParsingRng();
			$values['elements'] = $rngParser->buildFormElements($idRelaxNGNode, 'custom_info');
		}

		// Getting languages
        $language = new Language();
        $languages = $language->getLanguagesForNode($nodeId);
        if ($languages) {
        	$values['default_language'] = $languages[0]['IdLanguage'];
			$values['languages'] = $languages;
        	$values['json_languages'] = json_encode($languages);
        }
		
		$values['nodeid'] = $nodeId;
		$values['go_method'] = 'update_metadata';

		$this->render($values, '', 'default-3.0.tpl');
	}





	/**
	 * Save the results from the form
	 */
	public function save_metadata() {

		# Add some code here
		
	}



}
?>
