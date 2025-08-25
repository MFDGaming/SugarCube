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

namespace pocketmine\level\generator;


use pocketmine\Thread;
use pmmp\thread\ThreadSafeArray;

class GenerationThread extends Thread{

	protected $loadPaths;
	/** @var \ClassLoader */
	protected $loader;
	/** @var \ThreadedLogger */
	protected $logger;

	/** @var ThreadSafeArray */
	protected $externalQueue;
	/** @var ThreadSafeArray */
	protected $internalQueue;

	/**
	 * @return ThreadSafeArray
	 */
	public function getInternalQueue(){
		return $this->internalQueue;
	}

	/**
	 * @return ThreadSafeArray
	 */
	public function getExternalQueue(){
		return $this->externalQueue;
	}

	public function pushMainToThreadPacket($str){
		$this->internalQueue[] = $str;
		$this->synchronized(function (): void{
			$this->notify();
		});
	}

	public function readMainToThreadPacket(){
		return $this->internalQueue->shift();
	}

	public function pushThreadToMainPacket($str){
		$this->externalQueue[] = $str;
	}

	public function readThreadToMainPacket(){
		return $this->externalQueue->shift();
	}

	/**
	 * @return \ThreadedLogger
	 */
	public function getLogger(){
		return $this->logger;
	}

	public function __construct(\ThreadedLogger $logger, \ClassLoader $loader){
		$this->loader = $loader;
		$this->logger = $logger;
		$loadPaths = [];
		$this->addDependency($loadPaths, new \ReflectionClass($this->loader));
		$this->loadPaths = ThreadSafeArray::fromArray(array_reverse($loadPaths));

		$this->externalQueue = \ThreadedFactory::create();
		$this->internalQueue = \ThreadedFactory::create();

		$this->start();
	}

	protected function addDependency(array &$loadPaths, \ReflectionClass $dep){
		if($dep->getFileName() !== false){
			$loadPaths[$dep->getName()] = $dep->getFileName();
		}

		if($dep->getParentClass() instanceof \ReflectionClass){
			$this->addDependency($loadPaths, $dep->getParentClass());
		}

		foreach($dep->getInterfaces() as $interface){
			$this->addDependency($loadPaths, $interface);
		}
	}

	public function run(): void{
		error_reporting(-1);
		gc_enable();
		//Load removed dependencies, can't use require_once()
		foreach($this->loadPaths as $name => $path){
			if(!class_exists($name, false) and !interface_exists($name, false)){
				require($path);
			}
		}
		$this->loader->register();

		$generationManager = new GenerationManager($this, $this->getLogger(), $this->loader);
	}
}
