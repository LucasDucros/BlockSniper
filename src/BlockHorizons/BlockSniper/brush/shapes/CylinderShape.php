<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\brush\shapes;

use BlockHorizons\BlockSniper\brush\BaseShape;
use BlockHorizons\BlockSniper\sessions\SessionManager;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

class CylinderShape extends BaseShape {

	/** @var int */
	protected $radius = 0;
	/** @var int */
	protected $height = 0;
	/** @var bool */
	private $trueCircle = false;
	/** @var int */
	protected $id = self::SHAPE_CYLINDER;

	public function __construct(Player $player, Level $level, int $radius, Position $center, bool $hollow = false, bool $cloneShape = false) {
		parent::__construct($player, $level, $center, $hollow);
		$this->radius = $radius;
		$this->height = SessionManager::getPlayerSession($player)->getBrush()->getHeight();
		if($cloneShape) {
			$this->center[1] += $this->height;
		}
		$this->trueCircle = SessionManager::getPlayerSession($player)->getBrush()->getPerfect();
	}

	/**
	 * @param bool $vectorOnly
	 *
	 * @return array
	 */
	public function getBlocksInside(bool $vectorOnly = false): array {
		$radiusSquared = ($this->radius + ($this->trueCircle ? 0 : -0.5)) ** 2 + ($this->trueCircle ? 0.5 : 0);
		[$targetX, $targetY, $targetZ] = $this->center;

		$minX = $targetX - $this->radius;
		$minZ = $targetZ - $this->radius;
		$minY = $targetY - $this->height;
		$maxX = $targetX + $this->radius;
		$maxZ = $targetZ + $this->radius;
		$maxY = $targetY + $this->height;

		$blocksInside = [];

		for($x = $minX; $x <= $maxX; $x++) {
			for($z = $minZ; $z <= $maxZ; $z++) {
				for($y = $minY; $y <= $maxY; $y++) {
					if(($targetX - $x) ** 2 + ($targetZ - $z) ** 2 <= $radiusSquared) {
						if($this->hollow === true) {
							if($y !== $maxY && $y !== $minY && (($targetX - $x) ** 2 + ($targetZ - $z) ** 2) < $radiusSquared - 3 - $this->radius / 0.5) {
								continue;
							}
						}
						$blocksInside[] = $vectorOnly ? new Vector3($x, $y, $z) : $this->getLevel()->getBlock(new Vector3($x, $y, $z));
					}
				}
			}
		}
		return $blocksInside;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->hollow ? "Hollow Standing Cylinder" : "Standing Cylinder";
	}

	/**
	 * @return int
	 */
	public function getApproximateProcessedBlocks(): int {
		if($this->hollow) {
			$blockCount = (M_PI * $this->radius * $this->radius * 2) + (2 * M_PI * $this->radius * $this->height * 2);
		} else {
			$blockCount = $this->radius * $this->radius * M_PI * $this->height;
		}

		return (int) ceil($blockCount);
	}

	/**
	 * Returns the height of the shape.
	 *
	 * @return int
	 */
	public function getHeight(): int {
		return $this->height;
	}

	/**
	 * Returns the radius of the cylinder.
	 *
	 * @return int
	 */
	public function getRadius(): int {
		return $this->radius;
	}

	/**
	 * @return array
	 */
	public function getTouchedChunks(): array {
		$maxX = $this->center[0] + $this->radius;
		$minX = $this->center[0] - $this->radius;
		$maxZ = $this->center[2] + $this->radius;
		$minZ = $this->center[2] - $this->radius;

		$touchedChunks = [];
		for($x = $minX; $x <= $maxX + 16; $x += 16) {
			for($z = $minZ; $z <= $maxZ + 16; $z += 16) {
				$chunk = $this->getLevel()->getChunk($x >> 4, $z >> 4, true);
				$touchedChunks[Level::chunkHash($x >> 4, $z >> 4)] = $chunk->fastSerialize();
			}
		}
		return $touchedChunks;
	}
}
