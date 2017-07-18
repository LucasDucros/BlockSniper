<?php

namespace BlockHorizons\BlockSniper\brush\async;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\SimpleChunkManager;
use pocketmine\math\Vector3;

class BlockSniperChunkManager extends SimpleChunkManager {

	public function __construct($seed, $worldHeight = Level::Y_MAX) {
		parent::__construct($seed, $worldHeight);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $id
	 */
	public function setBiomeIdAt(int $x, int $z, int $id) {
		if($chunk = $this->getChunk($x >> 4, $z >> 4)) {
			$chunk->setBiomeId($x & 0x0f, $z & 0x0f, $id);
		}
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBiomeIdAt(int $x, int $z): int {
		if($chunk = $this->getChunk($x >> 4, $z >> 4)) {
			return $chunk->getBiomeId($x, $z);
		}
		return 0;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $side
	 *
	 * @return Block
	 */
	public function getSide(int $x, int $y, int $z, int $side): Block {
		if($chunk = $this->getChunk($x >> 4, $z >> 4)) {
			$block = Block::get($this->getSideId($x, $y, $z, $side), $this->getSideData($x, $y, $z, $side));
			$pos = [];
			switch($side) {
				case Vector3::SIDE_DOWN:
					$pos = [$x, $y - 1, $z];
					break;
				case Vector3::SIDE_UP:
					$pos = [$x, $y + 1, $z];
					break;
				case Vector3::SIDE_NORTH:
					$pos = [$x, $y, $z - 1];
					break;
				case Vector3::SIDE_SOUTH:
					$pos = [$x, $y, $z + 1];
					break;
				case Vector3::SIDE_WEST:
					$pos = [$x - 1, $y, $z];
					break;
				case Vector3::SIDE_EAST:
					$pos = [$x + 1, $y, $z];
			}
			$block->setComponents($pos[0], $pos[1], $pos[2]);
			return $block;
		}
		return Block::get(Block::AIR);
	}

	public function getSideId(int $x, int $y, int $z, int $side): int {
		if($chunk = $this->getChunk($x >> 4, $z >> 4)) {
			switch($side) {
				case Vector3::SIDE_DOWN:
					return $chunk->getBlockId($x, $y - 1, $z);
				case Vector3::SIDE_UP:
					return $chunk->getBlockId($x, $y + 1, $z);
				case Vector3::SIDE_NORTH:
					return $chunk->getBlockId($x, $y, $z - 1);
				case Vector3::SIDE_SOUTH:
					return $chunk->getBlockId($x, $y, $z + 1);
				case Vector3::SIDE_WEST:
					return $chunk->getBlockId($x - 1, $y, $z);
				case Vector3::SIDE_EAST:
					return $chunk->getBlockId($x + 1, $y, $z);
				default:
					return -1;
			}
		}
		return -1;
	}

	public function getSideData(int $x, int $y, int $z, int $side): int {
		if($chunk = $this->getChunk($x >> 4, $z >> 4)) {
			switch($side) {
				case Vector3::SIDE_DOWN:
					return $chunk->getBlockData($x, $y - 1, $z);
				case Vector3::SIDE_UP:
					return $chunk->getBlockData($x, $y + 1, $z);
				case Vector3::SIDE_NORTH:
					return $chunk->getBlockData($x, $y, $z - 1);
				case Vector3::SIDE_SOUTH:
					return $chunk->getBlockData($x, $y, $z + 1);
				case Vector3::SIDE_WEST:
					return $chunk->getBlockData($x - 1, $y, $z);
				case Vector3::SIDE_EAST:
					return $chunk->getBlockData($x + 1, $y, $z);
				default:
					return -1;
			}
		}
		return -1;
	}
}