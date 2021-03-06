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

namespace Ximdex\Runtime;

use DI\ContainerBuilder;
use Doctrine\Common\Cache\ArrayCache;

Class App
{
    const PREFIX = 'prefix';
    const SUFFIX = 'suffix';
    
    private static $instance;
    private static $DBInstance = array();
    private static $debug = false;
    
    protected $DIContainer;
    protected $DIBuilder;
    protected $config;

    public function __construct()
    {
        $this->DIBuilder = new ContainerBuilder();
        $this->DIBuilder->useAutowiring(true);
        $this->DIBuilder->useAnnotations(true);
        $this->DIBuilder->ignorePhpDocErrors(true);
        $this->DIBuilder->setDefinitionCache(new ArrayCache());
        $this->DIContainer = $this->DIBuilder->build();
        $this->config = array();
        if (self::$instance instanceof self) {
            throw new \Exception('-10, Cannot be instantiated more than once');
        } else {
            self::$instance = $this ;
        }
    }

    public static function getInstance()
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public static function config()
    {
        return self::getInstance()->getConfig();
    }

    public function getContainerObject(string $class)
    {
        return $this->DIContainer->get($class);
    }

    public static function getObject(string $class)
    {
        return self::getInstance()->getContainerObject($class);
    }

    public static function getValue(string $key, string $default = null)
    {
        return self::getInstance()->getRuntimeValue($key, $default);
    }

    public function getRuntimeValue(string $key, string $default = null)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return $default;
    }

    public static function setValue(string $key, $value, bool $persistent = false)
    {
        return self::getInstance()->setRuntimeValue($key, $value, $persistent);
    }

    public function setRuntimeValue(string $key, $value, $persistent = false)
    {
        if ($persistent) {
            $stm = self::db()->prepare('DELETE FROM Config WHERE ConfigKey = :key');
            $stm->execute(array(
                'key' => $key,
            ));
            $stm = self::db()->prepare('INSERT INTO Config (ConfigValue, ConfigKey) VALUES (:value, :key)');
            $stm->execute(array(
                'key' => $key,
                'value' => is_string($value) ? trim($value) : $value
            ));
        }
        $this->config[$key] = is_string($value) ? trim($value) : $value;
        return $this;
    }
    public static function addDbConnection(\PDO $connection, string $name = null)
    {
        if (is_null($name)) {
            $name  = self::getInstance()->getValue('default.db', 'db');
        }
        self::$DBInstance[$name] = $connection ;
    }

    /**
     * @param string $conf
     * @return \PDO|null
     * @throws \Exception
     */
    public static function db(string $conf = null)
    {
        if (is_null($conf)) {
            $conf = self::getInstance()->getValue('default.db', 'db');
        }
        if (! isset(self::$DBInstance[$conf])) {
            throw new \Exception( '-1,Unknown DB Connection');
        }
        return self::$DBInstance[$conf];
    }
    
    /**
     * Legacy: Compability
     * 
     * @param string $key
     * @return Object
     */
    public static function get(string $key)
    {
        $value = self::getInstance()->getRuntimeValue($key, null);
        if (! is_null($value)) {
            return $value;
        }
        $objectData = self::getInstance()->getRuntimeValue('class::definition::' . $key, null);
        if (! is_null($objectData)) {
            require_once(XIMDEX_ROOT_PATH . $objectData);
        }
        return self::getObject($key);
    }

    /**
     * getUrl forms an url to $suburl of APP using params if this exists
     * 
     * @param string $suburl
     * @param bool $includeUrlRoot
     * @param ...$params
     * @return string
     */
    public static function getUrl(string $suburl, bool $includeUrlRoot = true, ...$params)
    {
        if (! empty($params)) {
            $suburl = @sprintf($suburl, ...$params);
        }
        $base = (($includeUrlRoot) ? App::GetValue('UrlRoot') : '') . App::getValue('UrlFrontController');
        $url = $base. '/' . ltrim($suburl, '/');
        if ($url[0] != '/') {
            $url = '/' . $url;
        }
        return $url;
    }

    /**
     * getUrl forms an url to $suburl of Ximdex using params if this exists
     *
     * @param string $suburl
     * @param array ...$params
     * @return string
     */
    public static function getXimdexUrl(string $suburl, ...$params)
    {
        if (! empty($params)) {
            $suburl = sprintf($suburl, ...$params);
        }
        $base = trim(App::getValue('UrlRoot'), '/');
        $url =   $base. '/' . ltrim($suburl, '/');
        if ($url[0] != '/') {
            $url = '/' . $url;
        }
        return $url;
    }

    /**
     * getPath forms an path to $subpath  of APP  using params if this exists
     * 
     * @param string $subpath
     * @param array ...$params
     * @return string
     */
    public static function getPath(string $subpath, ...$params)
    {
        if (! empty($params)) {
            $subpath = sprintf($subpath, ...$params);
        }
        return APP_ROOT_PATH. '/' . ltrim($subpath, '/');
    }

    /**
     * Get the in application debug value
     * 
     * @return boolean
     */
    public static function debug()
    {
        return self::$debug;
    }
}
