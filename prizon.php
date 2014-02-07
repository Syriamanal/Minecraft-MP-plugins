<?php
  /*
__PocketMine Plugin__
name=Prizon
description=Prizon
version=0.1
author=WreWolf
class=Prizon
apiversion=9
*/
  /*

Small Changelog
===============

0.1:

*/
  class Prizon implements Plugin
  {
    private $api, $prizons;

    public function __construct(ServerAPI $api, $server = false)
    {
      $this->api      = $api;
      $this->interval = array();
    }

    public function init()
    {
      $this->createConfig();
      $this->api->event('player.join', array($this, 'handle'));
      $this->api->console->register('prizon', 'Jail player on the server.', array($this, 'commandH'));
      $this->api->console->register('unprizon', 'Release player on the server.', array($this, 'commandH'));
      $this->api->console->register('prizonlist', 'Prizon players on the server.', array($this, 'commandH'));
      $this->api->console->register('setprizon', 'setprizon <x> <y> <z>', array($this, 'commandH'));
    }

    public function createConfig()
    {
      $default       = array("prizon" => array("x" => 0, "y" => 0, "z" => 0), "users" => array());
      $this->path    = $this->api->plugin->createConfig($this, $default);
      $this->prizons = $this->api->plugin->readYAML($this->path . "config.yml");
    }

    public function saveConfig()
    {
      $this->api->plugin->writeYAML($this->path . "config.yml", $this->prizons);
    }

    public function commandH($cmd, $params, $issuer, $alias)
    {
      $output = "";
      switch ($cmd) {
        case 'setprizon':


          array_push($this->prizons['users'], $issuer->username);
          $this->prizons['prizon']['x'] = $issuer->entity->x;
          $this->prizons['prizon']['y'] = $issuer->entity->y;
          $this->prizons['prizon']['z'] = $issuer->entity->z;

          $this->saveConfig();
          $this->api->chat->sendTo(false, "Prison position seted", $issuer->username);


          break;
        case 'prizon':
          $user = $this->api->player->get($params[0]);
          if ($user instanceof Player) {
            array_push($this->prizons['users'], $user->username);
            $this->saveConfig();
            $level     = $this->api->level->getDefault();
            $prisonPos = new Position($this->prizons['prizon']['x'], $this->prizons['prizon']['y'], $this->prizons['prizon']['z'], $level);
            $user->setSpawn($prisonPos);
            $user->teleport($prisonPos);

            $this->api->chat->broadcast("User " . $user->username . " jailed by " . $issuer->username);
          }
          break;
        case "unprizon":
          $user = $this->api->player->get($params[0]);
          if ($user instanceof Player) {
            $this->prizons['users'] = array_diff($this->prizons['users'], array($user->username));
            $this->saveConfig();
            $level = $this->api->level->getDefault();

            $user->setSpawn($level->getSpawn());
            $user->teleport($level->getSpawn());
            $this->api->chat->broadcast("User " . $user->username . " released from jail by " . $issuer->username);
          }
          break;
        case "prizonlist":
          $x      = $this->prizons['prizon']['x'];
          $y      = $this->prizons['prizon']['y'];
          $z      = $this->prizons['prizon']['z'];
          $output = "Prizon position ($x, $y, $z) \n ";
          foreach ($this->prizons['users'] as $users) {
            $output .= $users . ", ";
          }
          break;
      }
      return $output;
    }

    public function __destruct()
    {
    }

    public function handle(&$data, $event)
    {
      switch ($event) {
        case "player.join":
          $time_start= microtime(true);
          //console("[debug] join " . $data->username);

          $level     = $this->api->level->getDefault();
          $prisonPos = new Position($this->prizons['prizon']['x'], $this->prizons['prizon']['y'], $this->prizons['prizon']['z'], $level);
          $user      = $data->username;

          if (in_array($user, $this->prizons['users'])) {
            $target = $this->api->player->get($user);
if(!isset($target)) break;
            $target->setSpawn($prisonPos);
            $target->teleport($prisonPos);
          }
          $time = microtime(true) - $time_start;
          console("prizone registered. runtime: $time");
          break;
      }
    }
  }
