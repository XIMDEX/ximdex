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

namespace Ximdex\Runtime;

use Ximdex\Models\User;

/** @const DEFAULT_SESSION - Name of default session */
define('DEFAULT_SESSION', 'SessionID');

/** @const HTTP_SESSION_STARTED - The session was started with the current request */
define('HTTP_SESSION_STARTED', 1);

/** @const HTTP_SESSION_STARTED - No new session was started with the current request */
define('HTTP_SESSION_CONTINUED', 2);

class Session
{
    /**
     * Set new name of a session
     *
     * @static
     * @access public
     * @param string $name New name of a session
     * @return string
     */
    public static function name(string $name = null)
    {
        if (session_status() != PHP_SESSION_ACTIVE and $name) {
            return session_name($name);
        }
        return session_name();
    }

    /**
     * @param string $name
     * @param int $id
     */
    public static function start(string $name = DEFAULT_SESSION, int $id = null) {
        if (session_status() != PHP_SESSION_ACTIVE) {
            unset($id);
            self::name($name);
            session_cache_limiter('none');
            session_cache_expire(60);
            session_start();
            if (! isset($_SESSION['__HTTP_Session_Info'])) {
                $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_STARTED;
            } else {
                $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_CONTINUED;
            }
        }
    }
    
    public static function refresh()
    {
        $sid = session_id();
        if (empty($sid)) {
            self::start();
        }
        session_regenerate_id();
        setcookie(ini_get('session.name'),
            session_id(), time() . ini_get('session.cookie_lifetime'),
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure'),
            ini_get('session.cookie_httponly')
        );
    }

    public static function isNew()
    {
        return ! isset($_SESSION['__HTTP_Session_Info']) || $_SESSION['__HTTP_Session_Info'] == HTTP_SESSION_STARTED;
    }

    public static function set(string $key, $data)
    {
        $sid = session_id();
        if (empty($sid)) {
            self::start();
        }
        $_SESSION[$key] = $data;
    }

    public static function delete(string $key)
    {
        if (self::exists($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function get(string $key)
    {
        $ret = null;
        if (self::exists($key)) {
            $ret = $_SESSION[$key];
        }
        return $ret;
    }

    public static function exists(string $key)
    {
        $sid = session_id();
        if (empty($sid)) {
            self::start();
        }
        $ret = isset($_SESSION[$key]);
        return $ret;
    }

    public static function serialize(string $key, string & $var)
    {
        $_SESSION[$key] = serialize($var);
    }

    public static function & unserialize(string $key)
    {
        if (self::exists($key)) {
            $o = unserialize($_SESSION[$key]);
            return $o;
        }
		return NULL;
    }

    public static function destroy()
    {
        self::start();
        if (! empty($_SESSION)) {
            session_unset();
            $_SESSION = array();
            session_destroy();
        }
    }

    public static function check(bool $redirect = true)
    {
        self::start();
        if (! array_key_exists('action', $_GET) ) {
            $_GET['action'] = null;
        }
        if (! self::exists('logged') && 'installer' != $_GET['action']) {
            if ($redirect) {
                $response = new Response();
                $response->sendStatus(sprintf('Location: %s/', App::getValue('UrlRoot')), true, 301);
                setcookie('expired', '1', time() + 60);
                die();
            }
            return false;
        }
		return true;
    }

    public static function checkUserID()
    {
        $userID = self::get('userID');
        if ($userID == User::XIMDEX_ID) {
            return true;
        }
        return false;
    }
}