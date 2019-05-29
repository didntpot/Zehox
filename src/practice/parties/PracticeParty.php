<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-24
 * Time: 11:21
 */

declare(strict_types=1);

namespace practice\parties;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\PracticeCore;
use practice\PracticeUtil;

class PracticeParty
{

    private $players;

    private $leader;

    private $name;

    private static $partyID = 0;

    private $id;

    private $open;

    public function __construct(string $leader, string $name = null)
    {
        $this->leader = $leader;
        $this->name = (!is_null($name)) ? $name : 'Party_' . (self::$partyID + 1);
        $this->id = self::$partyID;
        $this->players = [$leader => true];
        $this->open = true;
        self::$partyID++;
    }

    public function isPartyOpen() : bool {
        return $this->open;
    }

    public function setPartyOpen(bool $open) : self {

        $msg = null;

        //TODO ADD TO MESSAGES.YML

        if($this->open === false and $open === true)
            $msg = TextFormat::GREEN . 'Party is now open for anyone to join.';
        elseif ($this->open === true and $open === false) $msg = TextFormat::RED . 'Party is now invite-only.';

        if(!is_null($msg))
            $this->broadcastMsg($msg);

        $this->open = $open;
        return $this;
    }

    public function addToParty(string $player) : void {

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($player);

            $pl = $p->getPlayer();

            PracticeCore::getItemHandler()->spawnPartyItems($pl, true);

            $this->players[$p->getPlayerName()] = true;

            $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('party.general.join-msg'), ["%player%" => $player]);

            $this->broadcastMsg($msg);
        }
    }

    public function isInParty(string $player) : bool {
        return PracticeCore::getPlayerHandler()->isPlayerOnline($player) and isset($this->players[$player]);
    }

    public function removeFromParty(string $player, bool $kick = false) : bool {

        $permanentlyRemove = false;

        if($this->isInParty($player)) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($player);

            if($this->isLeaderOfParty($player)) {

                $size = count($this->players);

                if($size > 1) {

                    $keys = array_keys($this->players);
                    $newLeader = null;

                    foreach($keys as $key) {
                        if($key !== $player and PracticeCore::getPlayerHandler()->isPlayerOnline($key)) {
                            $newLeader = strval($key);
                            break;
                        }
                    }

                    if(!is_null($newLeader)) {
                        $this->leader = $newLeader;
                        $newL = PracticeCore::getPlayerHandler()->getPlayer($newLeader);
                        $newLeaderMsg = PracticeUtil::str_replace(PracticeUtil::getMessage('party.general.new-manager'), ['%player%' => 'You']);
                        $newL->sendMessage($newLeaderMsg);
                    }

                    else $permanentlyRemove = true;

                } else $permanentlyRemove = true;
            }

            $leaveMsg = PracticeUtil::getMessage('party.general.leave-msg');
            $kickMsg = PracticeUtil::getMessage('party.kick.message');

            $singularMsg = ($kick === true) ? PracticeUtil::str_replace($kickMsg, ['%player%' => 'You', 'has' => 'have been']) : PracticeUtil::str_replace($leaveMsg, ['%player%' => 'You', 'has' => 'have']);

            $p->sendMessage($singularMsg);

            unset($this->players[$player]);

            $msg = ($kick === true) ? PracticeUtil::str_replace($kickMsg, ['%player%' => $player]) : PracticeUtil::str_replace($leaveMsg, ['%player%' => $player]);
            $this->broadcastMsg($msg);
        }

        return $permanentlyRemove;
    }

    public function isLeaderOfParty(string $player) : bool {
        $result = false;
        if($this->isInParty($player))
            $result = $this->leader === $player;
        return $result;
    }

    private function broadcastMsg(string $msg, string $pl = null) : void {

        $keys = array_keys($this->players);

        foreach($keys as $key) {

            $exec = true;

            if(!is_null($pl) and $pl === $key)
                $exec = false;


            if($exec === true and PracticeCore::getPlayerHandler()->isPlayerOnline($key)) {
                $p = PracticeCore::getPlayerHandler()->getPlayer($key);
                $p->sendMessage($msg);
            }
        }
    }

    public function getPartyName() : string {
        return $this->name;
    }


    /**
     * @return array|string[]
     */
    public function getMembers() : array {
        return array_keys($this->players);
    }

    public function getId() : int {
        return $this->id;
    }
}