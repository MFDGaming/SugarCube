<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine;
use pmmp\thread\Thread as PMMPThread;

/**
 * This class must be extended by all custom threading classes
 */
abstract class Thread extends PMMPThread{
	protected $isKilled = false;

	public function start($options = PMMPThread::INHERIT_ALL): bool{
		ThreadManager::getInstance()->add($this);

		if(!$this->isRunning() and !$this->isJoined() and !$this->isTerminated()){
			return parent::start($options);
		}

		return false;
	}
	
	public function quit(){
		$this->isKilled = true;
		
		$this->notify();

		if(!$this->isJoined()){
			if(!$this->isTerminated()){
				$this->join();
			}
		}

		ThreadManager::getInstance()->remove($this);
	}
}