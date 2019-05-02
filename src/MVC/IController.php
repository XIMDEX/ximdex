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
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 */

namespace Ximdex\MVC;

use Ximdex\Logger;
use Ximdex\Runtime\Request;
use Ximdex\Runtime\Response;
use Ximdex\Utils\Messages;

/**
 * @brief Controller pseudo abstract class for Actions, Applications and Controllers
 *
 * Pseudo abstract class who serves as base for Actions, Applications and Controllers, provide
 * methods to manage the request object and errors
 */
class IController
{
    /**
     * Objeto Request para almacenar parámetros de petición
     * 
     * @var \Ximdex\Runtime\Request
     */
    public $request;
    
    /**
     * @var \Ximdex\Runtime\Response
     */
    public $response;
    
    /**
     * @var boolean
     */
    public $hasError = false;
    
    /**
     * @var string
     */
    public $msgError;
    
    /**
     * @var \Ximdex\Utils\Messages
     */
    public $messages;

    /**
     * IController constructor
     */
    public function __construct()
    {
        $this->hasError = false;
        $this->messages = new  Messages();
        $this->request = new Request();
        $this->response = new Response();
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    
    public function hasError()
    {
        if (isset ($this->hasError)) {
            return $this->hasError;
        }
    }

    public function getMsgError()
    {
        if (isset($this->msgError)) {
           return $this->msgError;
        }
    }

    private function setError(string $msg, string $module = null)
    {
        unset($module);
        $this->hasError = true;
        $this->msgError = $msg;
        
        // Registra un apunte en el log
        Logger::error($msg);
    }
}
