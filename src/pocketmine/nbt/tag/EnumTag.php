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

namespace pocketmine\nbt\tag;

use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\EnumTag as TagEnum;

#include <rules/NBT.h>

class EnumTag extends NamedTag implements \ArrayAccess, \Countable{

	private $tagType;

	public function __construct($name = "", $value = []){
		$this->name = $name;
		foreach($value as $k => $v){
			$this->{$k} = $v;
		}
	}

	public function &getValue(){
		$value = [];
		foreach($this as $k => $v){
			if($v instanceof Tag){
				$value[$k] = $v;
			}
		}

		return $value;
	}

	public function offsetExists($offset): bool{
		return isset($this->{$offset});
	}

	public function offsetGet($offset): mixed{
		if(isset($this->{$offset}) and $this->{$offset} instanceof Tag){
			if($this->{$offset} instanceof \ArrayAccess){
				return $this->{$offset};
			}else{
				return $this->{$offset}->getValue();
			}
		}

		return null;
	}

	public function offsetSet($offset, $value): void{
		if($value instanceof Tag){
			$this->{$offset} = $value;
		}elseif($this->{$offset} instanceof Tag){
			$this->{$offset}->setValue($value);
		}
	}

	public function offsetUnset($offset): void{
		unset($this->{$offset});
	}

	public function count($mode = COUNT_NORMAL): int{
		for($i = 0; true; $i++){
			if(!isset($this->{$i})){
				return $i;
			}
			if($mode === COUNT_RECURSIVE){
				if($this->{$i} instanceof \Countable){
					$i += count($this->{$i});
				}
			}
		}

		return $i;
	}

	public function getType(){
		return NBT::TAG_Enum;
	}

	public function setTagType($type){
		$this->tagType = $type;
	}

	public function getTagType(){
		return $this->tagType;
	}

	public function read(NBT $nbt){
		$this->value = [];
		$this->tagType = $nbt->getByte();
		$size = $nbt->getInt();
		for($i = 0; $i < $size and !$nbt->feof(); ++$i){
			switch($this->tagType){
				case NBT::TAG_Byte:
					$tag = new ByteTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Short:
					$tag = new ShortTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Int:
					$tag = new IntTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Long:
					$tag = new LongTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Float:
					$tag = new FloatTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Double:
					$tag = new DoubleTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_ByteArray:
					$tag = new ByteArrayTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_String:
					$tag = new StringTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Enum:
					$tag = new TagEnum(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_Compound:
					$tag = new CompoundTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
				case NBT::TAG_IntArray:
					$tag = new IntArrayTag(false);
					$tag->read($nbt);
					$this->{$i} = $tag;
					break;
			}
		}
	}

	public function write(NBT $nbt){
		if(!isset($this->tagType)){
			$id = null;
			foreach($this as $tag){
				if($tag instanceof Tag){
					if(!isset($id)){
						$id = $tag->getType();
					}elseif($id !== $tag->getType()){
						return false;
					}
				}
			}
			$this->tagType = $id;
		}

		$nbt->putByte($this->tagType);

		/** @var Tag[] $tags */
		$tags = [];
		foreach($this as $tag){
			if($tag instanceof Tag){
				$tags[] = $tag;
			}
		}
		$nbt->putInt(count($tags));
		foreach($tags as $tag){
			$tag->write($nbt);
		}
	}
}
