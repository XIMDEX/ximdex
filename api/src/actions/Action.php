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

namespace XimdexApi\actions;

use XimdexApi\core\Router;

abstract class Action
{
    protected const PREFIX = '';
    
    protected const ROUTES = [];
    
    protected const PUBLIC = [];

    public static function addMethods(Router $router)
    {
        foreach (static::ROUTES as $route => $action) {
            $router->addRoute(static::getPath($route), [static::class, $action]);
            if (in_array($route, static::PUBLIC)) {
                $router->addAllowedRequest(static::getPath($route));
            }
        }
    }

    protected static function getPath(string $action)
    {
        return (! empty(static::PREFIX) ? static::PREFIX . '/' : '') . $action;
    }
}
