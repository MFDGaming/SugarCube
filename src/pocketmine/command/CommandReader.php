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

namespace pocketmine\command;

use pocketmine\Thread;

class CommandReader extends Thread{
	private $readline;
	protected $shutdown = false;

	/** @var \Threaded */
	protected $buffer;

	/**
	 * @param \Threaded $threaded
	 */
	public function __construct(){
		$this->buffer = \ThreadedFactory::create();
		$this->start();
	}

	private function readLine(){
		if(!$this->readline){
			$line = trim(fgets(fopen("php://stdin", "r")));
		}else{
			$line = trim(readline("> "));
			if($line != ""){
				readline_add_history($line);
			}
		}

		return $line;
	}

	/**
	 * Reads a line from console, if available. Returns null if not available
	 *
	 * @return string|null
	 */
	public function getLine(){
		if($this->buffer->count() !== 0){
			return $this->buffer->synchronized(function (){
				return $this->buffer->shift();
			});
		}

		return null;
	}
	
	public function shutdown(){
		$this->shutdown = true;
	}

	public function run(){
		$opts = getopt("", ["disable-readline"]);
		if(extension_loaded("readline") and !isset($opts["disable-readline"])){
			$this->readline = true;
		}else{
			global $stdin;
			$stdin = fopen("php://stdin", "r");
			stream_set_blocking($stdin, 0);
			$this->readline = false;
		}
		
		$lastLine = microtime(true);
		while(!$this->shutdown){
			if(($line = $this->readLine()) !== ""){
				$this->buffer[] = preg_replace("#\\x1b\\x5b([^\\x1b]*\\x7e|[\\x40-\\x50])#", "", $line);
			}elseif(!$this->shutdown and (microtime(true) - $lastLine) <= 0.1){ //Non blocking! Sleep to save CPU
				$this->synchronized(function(): void{
					$this->wait(10000);
				});
			}
			$lastLine = microtime(true);
		}
	}
}
