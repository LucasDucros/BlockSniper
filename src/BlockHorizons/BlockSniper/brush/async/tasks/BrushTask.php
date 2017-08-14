<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\brush\async\tasks;

use BlockHorizons\BlockSniper\brush\BaseShape;
use BlockHorizons\BlockSniper\brush\BaseType;
use BlockHorizons\BlockSniper\Loader;
use BlockHorizons\BlockSniper\undo\Undo;
use pocketmine\block\Block;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\Server;

class BrushTask extends AsyncBlockSniperTask {

	/** @var BaseShape */
	private $shape = null;
	/** @var string */
	private $chunks = "";
	/** @var BaseType */
	protected $type = null;

	public function __construct(BaseShape $shape, BaseType $type, array $chunks) {
		$this->shape = $shape;
		$this->type = $type;
		$this->chunks = serialize($chunks);
	}

	public function onRun() {
		$chunks = unserialize($this->chunks);
		$processedBlocks = 0;
		$type = $this->type;
		$shape = $this->shape;
		foreach($chunks as $hash => $data) {
			$chunks[$hash] = Chunk::fastDeserialize($data);
		}
		/** @var Chunk[] $chunks */
		$vectorsInside = $shape->getBlocksInside(true);
		$blocks = [];
		$manager = BaseType::establishChunkManager($chunks);
		$i = 0;
		foreach($vectorsInside as $vector3) {
			$index = Level::chunkHash($vector3->x >> 4, $vector3->z >> 4);
			if(isset($chunks[$index])) {
				$pos = [$vector3->x & 0x0f, $vector3->y, $vector3->z & 0x0f];
				$block = Block::get($chunks[$index]->getBlockId($pos[0], $pos[1], $pos[2]), $chunks[$index]->getBlockData($pos[0], $pos[1], $pos[2]));
				$block->setComponents($vector3->x, $vector3->y, $vector3->z);
				$blocks[] = $block;
				$processedBlocks++;
			}
			if(++$i === (int) ($shape->getApproximateProcessedBlocks() / 100)) {
				$this->publishProgress(round($processedBlocks / $shape->getApproximateProcessedBlocks() * 100) . "%");
				$i = 0;
			}
		}
		$type->setBlocksInside($blocks);
		$type->setAsynchronous();
		$type->setChunkManager($manager);
		$undoBlocks = $type->fillShape();

		$serializedChunks = $chunks;
		foreach($serializedChunks as &$chunk) {
			$chunk = $chunk->fastSerialize();
		}
		unset($chunk);

		$this->setResult([
			"undoBlocks" => $undoBlocks,
			"chunks" => $serializedChunks
		]);
	}

	/**
	 * @param Server $server
	 *
	 * @return bool
	 */
	public function onCompletion(Server $server): bool {
		/** @var Loader $loader */
		$loader = $server->getPluginManager()->getPlugin("BlockSniper");
		if($loader === null) {
			return false;
		}
		if(!$loader->isEnabled()) {
			return false;
		}
		$result = $this->getResult();
		$chunks = $result["chunks"];
		foreach($chunks as &$chunk) {
			$chunk = Chunk::fastDeserialize($chunk);
		}
		unset($chunk);
		$undoBlocks = $result["undoBlocks"];
		$level = $server->getLevel($this->shape->getLevelId());
		if($level instanceof Level) {
			foreach($chunks as $hash => $chunk) {
				$x = $z = 0;
				Level::getXZ($hash, $x, $z);
				$level->setChunk($x, $z, $chunk);
			}
		}
		if($player = $this->shape->getPlayer($server)) {
			$loader->getRevertStorer()->saveRevert((new Undo($undoBlocks, BaseType::establishChunkManager($chunks), $chunks))->setPlayerName($this->shape->getPlayer($server)->getName()), $player);
		}
		return true;
	}

	/**
	 * @param Server $server
	 * @param mixed  $progress
	 *
	 * @return bool
	 */
	public function onProgressUpdate(Server $server, $progress): bool {
		$loader = $server->getPluginManager()->getPlugin("BlockSniper");
		if($loader instanceof Loader) {
			if($loader->isEnabled()) {
				$loader->getLogger()->debug($progress);
				return true;
			}
		}
		$this->setGarbage();
		return false;
	}
}