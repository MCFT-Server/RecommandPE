<?php
namespace maru;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\Listener;
class RecommandPe extends PluginBase implements Listener{
	public $config, $recommandDB, $uuidlist;
	public function onEnable() {
		if ($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") == null) {
			$this->getLogger()->error("EconomyAPI 플러그인이 없어서 플러그인을 비활성화 합니다.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		@mkdir($this->getDataFolder());
		$this->loadConfig();
		$this->loadRecommadDB();
		$this->loadUUIDlist();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable() {
		$this->save(true);
	}
	public function loadConfig() {
		$this->saveResource("config.yml");
		$this->config = (new Config($this->getDataFolder()."config.yml", Config::YAML))->getAll();
	}
	public function loadRecommadDB() {
		$this->recommandDB = (new Config($this->getDataFolder()."recommandDB.json", Config::JSON))->getAll();
	}
	public function loadUUIDlist() {
		$this->uuidlist = (new Config($this->getDataFolder()."uuidlist.json", Config::JSON))->getAll();
	}
	public function save($async) {
		$recommandDB = new Config($this->getDataFolder()."recommandDB.json", Config::JSON);
		$recommandDB->setAll($this->recommandDB);
		$recommandDB->save($async);
		
		$uuidlist = new Config($this->getDataFolder()."uuidlist.json", Config::JSON);
		$uuidlist->setAll($this->uuidlist);
		$uuidlist->save($async);
	}
	public function onCommand(CommandSender $sender, Command $command, $label, Array $args) {
		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED."이 명령어는 게임에서만 입력 가능합니다.");
			return true;
		}
		if (!isset($args[0])) {
			return false;
		}
		if (isset($this->recommandDB[strtolower($sender->getName())])) {
			$sender->sendMessage(TextFormat::RED."당신은 이미 입력한 추천인이 있습니다.");
			return true;
		}
		if (!isset($this->uuidlist[strtolower($args[0])])) {
			$sender->sendMessage(TextFormat::RED.$args[0]."은 서버에 접속한 적이 없습니다.");
			return true;
		}
		if ($this->uuidlist[strtolower($sender->getName())] === $this->uuidlist[strtolower($args[0])] || strtolower($sender->getName()) == strtolower($args[0])) {
			$sender->sendMessage(TextFormat::RED."자기 자신을 추천할 수 없습니다.");
			return true;
		}
		EconomyAPI::getInstance()->addMoney($args[0], $this->config["recommand-prize"]);
		EconomyAPI::getInstance()->addMoney($sender, $this->config["commander-prize"]);
		$sender->sendMessage("추천인을 입력해서 {$this->config["recommand-prize"]}원을 얻었습니다.");
		$this->recommandDB[strtolower($sender->getName())] = strtolower($args[0]);
		return true;
	}
	public function onLogin(PlayerLoginEvent $event) {
		$player = $event->getPlayer();
		$this->uuidlist[strtolower($player->getName())] = $player->getClientId();
	}
}
?>