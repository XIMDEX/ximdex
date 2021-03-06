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

namespace Ximdex\Models;

use Ximdex\Logger;
use Ximdex\Models\ORM\PumpersOrm;
use Ximdex\Runtime\Db;

include_once XIMDEX_ROOT_PATH . '/src/Sync/conf/synchro_conf.php';

/**
 * @brief Handles operations with Pumpers
 *
 * A Pumper is an instance of the dexPumper script, wich is responsible for sending the ServerFrames to Server (via ftp, ssh, etc)
 * This class includes the methods that interact with the Database
 */
class Pumper extends PumpersOrm
{
    const NEW = 'New';
    
    const STARTING = 'Starting';
    
    const STARTED = 'Started';
    
    const ENDED = 'Ended';
    
    private $maxvoidcycles = 10;
    
    private $sleeptime = 2;

    /**
     * Sets the value of any variable
     * 
     * @param string key
     * @param unknown value
     */
    public function setFlag(string $key, string $value = null)
    {
        $this->$key = $value;
    }

    /**
     * Gets the value of any variable
     * 
     * @param string key
     */
    public function getFlag(string $key)
    {
        return $this->$key;
    }

    /**
     * Adds a row to Pumpers table
     * 
     * @param int idServer
     * @return int|null
     */
    public function create(int $idServer, int $maxVoidCycles = null) : ?int
    {
        if ($maxVoidCycles !== null) {
            $this->maxvoidcycles = $maxVoidCycles;
        }
        $this->set('IdServer', $idServer);
        $this->set('State', Pumper::NEW);
        $this->set('StartTime', time());
        $this->set('CheckTime', time());
        $this->set('ProcessId', 'xxxx');
        parent::add();
        $pumperID = $this->get('PumperId');
        if ($pumperID > 0) {
            return $pumperID;
        }
        Logger::error('Inserting pumper');
        return null;
    }

    /**
     * Gets the Pumpers whose state is different to Ended
     * 
     * @return array
     */
    public function getPumpersInRegistry() : array
    {
        $sql = "SELECT PumperId FROM Pumpers WHERE State != '" . Pumper::ENDED . "'";
        $dbObj = new \Ximdex\Runtime\Db();
        $dbObj->Query($sql);
        $pumpers = array();
        while (! $dbObj->EOF) {
            $pumpers[] = $dbObj->GetValue('PumperId');
            $dbObj->Next();
        }
        return $pumpers;
    }

    /**
     * Calls to command for start a Pumper
     * 
     * @param int $pumperId
     * @param string $mode
     * @param bool $runInBackground
     * @param int $delay
     * @return bool
     */
    public function startPumper(int $pumperId, string $mode = 'php', bool $runInBackground = true, int $delay = 0) : bool
    {
        $pumper = new Pumper($pumperId);
        if ($pumper->get('ProcessId') and Pumper::isAlive($pumper)) {
            
            // Terminate the previous pumper process
            if (Pumper::terminate($pumper)) {
                Logger::warning('Pumper with ID: ' . $pumperId . ' has been terminated (Process with PID: ' . $pumper->get('ProcessId'));
            } else {
                Logger::error('Cannot terminate the process with pid ' . $pumper->get('ProcessId') . ' for pumper ' . $pumperId);
            }
        }
        
        // Initialize the pumper to Starting state
        $this->set('State', Pumper::STARTING);
        $this->update();
        $startCommand = 'php ' . XIMDEX_ROOT_PATH . '/bootstrap.php ' . PUMPERPHP_PATH . '/dexpumper.' . $mode 
            . " --pumperid=$pumperId --sleeptime=" . $this->sleeptime . ' --maxvoidcycles=' . $this->maxvoidcycles . ' --delay=' . $delay
            . ' --localbasepath=' . SERVERFRAMES_SYNC_PATH . ' > ' . sys_get_temp_dir() . '/pumpers.err';
        if ($runInBackground) { 
            $startCommand .= ' &';
        }
        Logger::debug("Pumper call: $startCommand");
        $var = 0;
        system($startCommand, $var);
        Logger::debug($startCommand);

        // This code return always 0 in $var when the command is started in background with ending &
        // 0: OK, 200: connection problem, 400: pumper registering
        if ($var == 0) {
            Logger::info("Pumper $pumperId started successfully");
            return true;
        }
        if ($var == 200) {
            Logger::error("In server connection starting pumper $pumperId");
            return false;
        }
        if ($var == 400) {
            Logger::error("Registering pumper $pumperId");
            return false;
        }
        Logger::error("Code $var starting pumper $pumperId");
        return false;
    }
    
    public static function isAlive(Pumper $pumper) : bool
    {
        if (! $pumper->get('PumperId')) {
            Logger::error('No ID was sent to checking pumper process status');
            return false;
        }
        if ($pumper->get('ProcessId') == 'xxxx') {
            return false;
        }
        if (! $pumper->get('ProcessId')) {
            Logger::error('Pumper with ID: ' . $pumper->get('PumperId') . ' has not a process ID');
            return false;
        }
        $running = posix_kill($pumper->get('ProcessId'), 0);
        if (posix_get_last_error() == 1) {
            $running = true;
        }
        return $running;
    }
    
    public static function terminate(Pumper $pumper) : bool
    {
        if (! $pumper->get('PumperId')) {
            Logger::error('No ID was sent to terminate pumper process');
            return false;
        }
        if (! $pumper->get('ProcessId') or $pumper->get('ProcessId') == 'xxxx') {
            Logger::error('Pumper with ID: ' . $pumper->get('PumperId') . ' has not a process ID');
            return false;
        }
        return posix_kill($pumper->get('ProcessId'), 9);
    }
    
    /**
     * Retrieve a total of pumpers matching any criteria
     * 
     * @param bool $active
     * @param int $serverId
     * @throws \Exception
     * @return int
     */
    public static function countPumpers(bool $active = true, int $serverId = null) : int
    {
        $sql = 'SELECT COUNT(PumperId) AS total FROM Pumpers WHERE TRUE';
        if ($active) {
            $sql .= ' AND State != \'' . self::ENDED . '\'';
        }
        if ($serverId) {
            $sql .= ' AND IdServer = ' . $serverId;
        }
        $dbObj = new Db();
        if ($dbObj->Query($sql) === false) {
            throw new \Exception($dbObj->getDesErr());
        }
        if ($dbObj->numRows) {
            return (int) $dbObj->GetValue('total');
        }
        return 0;
    }
}
