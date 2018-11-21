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
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 */

namespace Ximdex\Models;

use Ximdex\Logger;
use Ximdex\Models\ORM\ServersOrm;
use Ximdex\NodeTypes\ServerNode;
use Ximdex\Utils\Date;
use Ximdex\Runtime\Db;

class Server extends ServersOrm
{
    const MAX_CYCLES_TO_RETRY_PUMPING = 0;
    const SECONDS_TO_WAIT_FOR_RETRY_PUMPING = 60;
    
    private $serverNode;
    
    public function __construct(int $id = null)
    {
        parent::__construct($id);
        if ($id) {
            $this->setServerNode($id);
        }
    }
    
    public function setServerNode(int $id)
    {
        $this->serverNode = new ServerNode($id);
    }
    
    public function getChannels() : array
    {
        if (!$this->serverNode) {
            $this->setServerNode($this->get('IdServer'));
        }
        return $this->serverNode->GetChannels($this->get('IdServer'));
    }
    
    public function resetForPumping() : bool
    {
        if (!$this->CyclesToRetryPumping) {
            return true;
        }
        $this->ActiveForPumping = 1;
        $this->DelayTimeToEnableForPumping = null;
        $this->CyclesToRetryPumping = 0;
        if ($this->update() === false) {
            return false;
        }
        $message = 'Server ' . $this->Description . ' (' . $this->IdServer . ') is now able for pumping (connection successful)';
        Logger::info($message, true);
        // User::sendNotifications('Server ' . $this->Description . ' has been reactivated', $message);
        return true;
    }
    
    public function enableForPumping() : bool
    {
        Logger::info('Enabling the server ' . $this->Description . ' for pumping');
        $this->ActiveForPumping = 1;
        $this->DelayTimeToEnableForPumping = null;
        if ($this->update() === false) {
            return false;
        }
        return true;
    }
    
    public function disableForPumping(bool $nonStopServer = false, int $delayTime = null, bool $sendNotifications = true) : bool
    {
        if (! $this->ActiveForPumping) {
            return true;
        }
        $this->ActiveForPumping = 0;
        $this->CyclesToRetryPumping = $this->CyclesToRetryPumping + 1;
        if (!$nonStopServer or (self::MAX_CYCLES_TO_RETRY_PUMPING and $this->CyclesToRetryPumping > self::MAX_CYCLES_TO_RETRY_PUMPING)) {
                
            // Disable the server permanently
            $this->DelayTimeToEnableForPumping = null;
            if ($this->update() === false) {
                return false;
            }
            $message = 'Disabling the server ' . $this->Description . ' (' . $this->IdServer . ') for pumping permanently';
            Logger::error($message);
            if ($sendNotifications) {
                User::sendNotifications('Server ' . $this->Description . ' has been disabled permanently', $message);
            }
        } else {
            
            // Disable the server temporally
            if (!$delayTime) {
                $delayTime = pow($this->CyclesToRetryPumping, 3) * self::SECONDS_TO_WAIT_FOR_RETRY_PUMPING;
            }
            if ($delayTime > 86400) {
                
                // Max time to retry 24 hours
                $delayTime = 86400;
            }
            $this->DelayTimeToEnableForPumping = time() + $delayTime;
            if ($this->update() === false) {
                return false;
            }
            $message = 'Server ' . $this->Description . ' (' . $this->IdServer . ') have been delayed temporally for pumping after cycle '
                . $this->CyclesToRetryPumping . ' (Will be restarted at ' . Date::formatTime($this->get('DelayTimeToEnableForPumping')) . ')';
            Logger::warning($message);
            if ($sendNotifications) {
                User::sendNotifications('Server ' . $this->Description . ' has been disabled temporally', $message);
            }
        }
        return true;
    }
    
    public function stats(int $portalId = null) : array
    {
        if (!$this->IdServer) {
            throw new \Exception('No server selected');
        }
        $sql = 'SELECT SUM(ServerFramesTotal) AS total, SUM(IF (`State` != \'' . Batch::STOPPED . '\', ServerFramesPending, 0)) AS pending';
        $sql .= ', SUM(IF (`State` != \'' . Batch::STOPPED . '\', ServerFramesActive, 0)) AS active';
        $sql .= ', SUM(IF (`State` = \'' . Batch::STOPPED . '\', ServerFramesActive + ServerFramesPending, 0)) AS stopped';
        $sql .= ', SUM(ServerFramesSuccess) AS success, SUM(ServerFramesFatalError) AS fatal, SUM(ServerFramesTemporalError) AS soft';
        $sql .= ' FROM Batchs WHERE ServerId = ' . $this->IdServer;
        if ($portalId) {
            $sql .= ' AND IdPortalFrame = ' . $portalId;
        }
        $sql .= ' GROUP BY ServerId';
        $db = new Db();
        if ($db->Query($sql) === false) {
            throw new \Exception('SQL error');
        }
        $stats = [];
        if (!$db->numRows) {
            throw new \Exception('There is not stats information for the server');
        }
        $stats['total'] = (int) $db->GetValue('total');
        $stats['pending'] = $this->get('ActiveForPumping') ? (int) $db->GetValue('pending') : 0;
        $stats['active'] = $this->get('ActiveForPumping') ? (int) $db->GetValue('active') : 0;
        $stats['success'] = (int) $db->GetValue('success');
        $stats['fatal'] = (int) $db->GetValue('fatal');
        $stats['soft'] = (int) $db->GetValue('soft');
        $stats['stopped'] = (int) $db->GetValue('stopped');
        $stats['delayed'] = !$this->get('ActiveForPumping') ? $db->GetValue('pending') + $db->GetValue('active') : 0;
        $stats['cancelled'] = (int) $db->GetValue('cancelled');
        return $stats;
    }
    
    /**
     * Returns a list of servers delayed by error
     * 
     * @throws \Exception
     * @return array
     */
    public static function getDelayed() : array
    {
        $sql = 'SELECT IdServer, Description FROM Servers WHERE ActiveForPumping = 0';
        $db = new Db();
        if ($db->Query($sql) === false) {
            throw new \Exception('SQL error in delayed servers retrieve');
        }
        $servers = [];
        while (! $db->EOF) {
            $servers[$db->GetValue('IdServer')] = $db->GetValue('Description');
            $db->Next();
        }
        return $servers;
    }
}
