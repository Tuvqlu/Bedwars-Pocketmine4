<?php
namespace VietnamPMTeam\BedWars\math;


use pocketmine\entity\{object\ItemEntity, Skin, Human};

use pocketmine\item\Item;

use pocketmine\math\Vector3;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Player;

class Generator extends Human {

    public const GEOMETRY = '{"geometry.player_head":{"texturewidth":64,"textureheight":64,"bones":[{"name":"head","pivot":[0,24,0],"cubes":[{"origin":[-4,0,-4],"size":[8,8,8],"uv":[0,0]}]}]}}';

    public $type;
    public $Glevel = 1;
    public $gdtime = 8;
    public $irtime = 8;
    public $dmtime = 25;
    public $emtime = 40;
    public $c = 0;
    public $start = 0;
    public $startPos;
    protected $gravity = 0;
    public $isMove = false;
    public $width = 0.5, $height = 0.6;
    public $i = 0;
    public $reverse = false;

    protected function initEntity(): void {
        parent::initEntity();
        $this->setNameTagAlwaysVisible(true);
        $this->setHealth(100);
        $this->startPos = $this->asVector3();
        $this->setFood(20);

    }

    public function onUpdate(int $currentTick):bool{
        if(!parent::onUpdate($currentTick) && $this->isClosed()){
            return false;
        }
   
        if($this->type !== "gold") {
           if($this->yaw >= 360){
             $this->yaw = 0;
           }
           $this->yaw += 5.5;

        }
        return true;
    }


    public function onDamage(EntityDamageEvent $source){
        $source->setCancelled();
    }


    public function setSkin(Skin $skin) : void{
        parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), '', 'geometry.player_head', self::GEOMETRY));
    }



    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->c++;
        if($this->c == 20){
            $this->start++;
            if($this->type !== "gold") {
                if($this->start == 5){
                    $this->start = 0;
                }
                if($this->start == 3){
                    $entities = $this->getLevel()->getPlayers();
                    foreach($entities as $player){
                        if($player instanceof Player){
                            if($player->distance($this->asVector3()) < 3){
                                $pk = new PlaySoundPacket();
                                $pk->x = $player->getX();
                                $pk->y = $player->getY();
                                $pk->z = $player->getZ();
                                $pk->volume = 100;
                                $pk->pitch = 1;
                                $pk->soundName = 'beacon.activate';
                                $player->dataPacket($pk);
                            } else {
                                if($player->distance($this->asVector3()) < 7){
                                    $pk = new PlaySoundPacket();
                                    $pk->x = $player->getX();
                                    $pk->y = $player->getY();
                                    $pk->z = $player->getZ();
                                    $pk->volume = 30;
                                    $pk->pitch = 1;
                                    $pk->soundName = 'beacon.activate';
                                    $player->dataPacket($pk);
                                }
                            }
                        }
                    }
                }
            }
            if($this->type == "gold"){
                $this->setNameTagVisible(false);
                $this->gdtime--;
                $this->irtime--;
                $level = "{$this->Glevel}";
                $Gmax = 6;
                $Imax = 1;
                $emerald = false;
                if($level >= 4){
                    $emerald = true;
                }
                if($emerald){
                    $this->emtime--;
                }
                if($this->emtime == 0){
                    $this->emtime = 40;
                    $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::EMERALD), new Vector3(0, -1, 0));
                }
                $p = 0;
                $i = 0;

                $entities = $this->getLevel()->getNearbyEntities($this->getBoundingBox()->expandedCopy(1, 1, 1));
                foreach($entities as $player){
                    if($player instanceof Player){
                        $p++;
                    }
                    if($player instanceof ItemEntity){
                        $i++;
                    }
                }
                $amount = 0;
                if($level < 2){
                    $amount = 1;
                }
                if($level > 2 && $level < 5){
                    $amount = 2;
                }
                if($level == 5){
                    $amount = 3;
                }
                if($this->gdtime == 0){
                    $this->gdtime = $Gmax;
                    if($p > 0){
                        foreach($entities as $player){
                            if($player instanceof Player && !$player->isSpectator()){
                                if($player->getInventory()->canAddItem(Item::get(Item::GOLD_INGOT, 0, $amount))){
                                    $this->addSound($player, 'random.pop', 1.5);
                                    $player->getInventory()->addItem(Item::get(Item::GOLD_INGOT, 0, $amount));
                                } else {
                                    $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::GOLD_INGOT, 0, $amount), new Vector3(0, -1, 0));
                                }
                            }
                        }
                    } else {
                        if($i > 0){
                            $itemEntity = null;
                            foreach($entities as $iEntity){
                                $itemEntity = $iEntity;
                            }
                            if($itemEntity instanceof ItemEntity){
                                if($itemEntity->getItem()->getId() == Item::GOLD_INGOT){
                                    $itemEntity->getItem()->setCount($itemEntity->getItem()->getCount() + $amount);
                                } else {
                                    $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::GOLD_INGOT, 0, $amount), new Vector3(0, -1, 0));
                                }
                            }
                        } else {
                            $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::GOLD_INGOT, 0, $amount), new Vector3(0, -1, 0));
                        }
                    }
                }
                $ironamount = 0;
                if($level < 2){
                    $ironamount = 1;
                }
                if($level > 1 && $level < 5){
                    $ironamount = 2;
                }
                if($level == 5){
                    $ironamount = 3;
                }
                if($this->irtime == 0){
                    $this->irtime = $Imax;
                    if($p > 0){
                        foreach($entities as $player){
                            if($player instanceof Player && !$player->isSpectator()){
                                if($player->getInventory()->canAddItem(Item::get(Item::IRON_INGOT, 0, $ironamount))){
                                    $this->addSound($player, 'random.pop', 1.5);
                                    $player->getInventory()->addItem(Item::get(Item::IRON_INGOT, 0, $ironamount));
                                } else {
                                    $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::IRON_INGOT, 0, $ironamount), new Vector3(0, -1, 0));
                                }
                            }
                        }
                    } else {
                        if($i > 0){
                            $itemEntity = null;
                            foreach($entities as $iEntity){
                                $itemEntity = $iEntity;
                            }
                            if($itemEntity instanceof ItemEntity){
                                if($itemEntity->getItem()->getId() == Item::IRON_INGOT){
                                    $itemEntity->getItem()->setCount($itemEntity->getItem()->getCount() + $ironamount);
                                } else {
                                    $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::IRON_INGOT, 0, $ironamount), new Vector3(0, -1, 0));
                                }
                            }
                        } else {
                            $this->level->dropItem($this->asVector3()->add(0, 0.5), Item::get(Item::IRON_INGOT, 0, $ironamount), new Vector3(0, -1, 0));
                        }
                    }
                }
            }
            if($this->type == "diamond"){
                $level = $this->Glevel;
                $tier = str_replace(["1", "2", "3"], ["I", "II", "III"], "{$level}");
                $this->dmtime--;
                $this->setNameTag("§bDiamond \n \n §eTier §c{$tier}\n§eSpawns in §c{$this->dmtime} §eseconds!");
                $max = null;
                if($level == 1){
                    $max = 25;
                }
                if($level == 2){
                    $max = 20;
                }
                if($level == 3){
                    $max = 15;
                }
                if($this->dmtime == 0){
                    $this->dmtime = $max;
                    $this->level->dropItem($this->asVector3()->add(0, -2), Item::get(Item::DIAMOND), new Vector3(0, -1, 0));
                }
            }
            if($this->type == "emerald"){
                $this->emtime--;
                $level = $this->Glevel;
                $tier = str_replace(["1", "2", "3"], ["I", "II", "III"], "{$level}");
                $this->setNameTag("§2Emerald \n \n §eTier §c{$tier}\n§eSpawns in §c{$this->emtime} §eseconds!");
                $max = null;
                if($level == 1){
                    $max = 40;
                }
                if($level == 2){
                    $max = 35;
                }
                if($level == 3){
                    $max = 30;
                }
                if($this->emtime == 0){
                    $this->emtime = $max;
                    $this->level->dropItem($this->asVector3()->add(0, -2), Item::get(Item::EMERALD), new Vector3(0, -1, 0));
                }
            }
            $this->c = 0;

        }
        return parent::entityBaseTick($tickDiff);
    }

    public function addSound($player, string $sound = '', float $pitch = 1){
        $pk = new PlaySoundPacket();
        $pk->x = $player->getX();
        $pk->y = $player->getY();
        $pk->z = $player->getZ();
        $pk->volume = 2;
        $pk->pitch = $pitch;
        $pk->soundName = $sound;
        $player->dataPacket($pk);
        //Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
    }
} 
