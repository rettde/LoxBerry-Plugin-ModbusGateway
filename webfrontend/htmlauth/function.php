<?php
//require_once "loxberry_system.php";
//require_once "loxberry_web.php";
require_once "Config/Lite.php";

function zmata_conf($confdir, $device, $speed, $mode, $trx_control, $port, $maxconn, $timeout, $retries, $pause, $wait) {
  // read cfg global file
  $serialcfg = $confdir. '/mbusd.cfg';
  $scfg = new Config_Lite("$serialcfg");
  $serialpath=$scfg->get(null,"SERIAL");
  if (!$serialpath) {
    $serialpath = '/dev/serial/by-id/';
  }
  // read conf file
  $file = $confdir. '/mbusd-'. $device. '.conf';
  $cfg = new Config_Lite("$file");
  $cfgdevice = $serialpath. $device;
  $cfg->setQuoteStrings(False);
  $cfg->set(null,"device",$cfgdevice);
  $cfg->set(null,"speed",$speed);
  $cfg->set(null,"mode",$mode);
  $cfg->set(null,"trx_control",$trx_control);
  $cfg->set(null,"port",$port);
  $cfg->set(null,"maxconn",$maxconn);
  $cfg->set(null,"timeout",$timeout);
  $cfg->set(null,"retries",$retries);
  $cfg->set(null,"pause",$pause);
  $cfg->set(null,"wait",$wait);
  $cfg->save();
}

function zmata_next_port($confdir, $baseport = 502) {
  $usedports = array();
  $mask = $confdir. '/mbusd-*.conf';
  foreach (glob($mask) as $file) {
    $cfg = new Config_Lite("$file");
    $port = $cfg->get(null, "port");
    if ($port) {
      $usedports[] = intval($port);
    }
  }
  $port = $baseport;
  while (in_array($port, $usedports)) {
    $port++;
  }
  return strval($port);
}

function zmata_port_in_use($confdir, $port, $excludedevice = null) {
  $mask = $confdir. '/mbusd-*.conf';
  foreach (glob($mask) as $file) {
    $cfg = new Config_Lite("$file");
    $existingport = $cfg->get(null, "port");
    if (intval($existingport) == intval($port)) {
      $devpath = $cfg->get(null, "device");
      $parts = explode("/", $devpath);
      $dev = end($parts);
      if ($excludedevice === null || $dev !== $excludedevice) {
        return $dev;
      }
    }
  }
  return false;
}

function zmata_cfg($confdir, $device, $loglevel) {
  $level = '-v'. $loglevel;
  $file = $confdir. '/mbusd-'. $device. '.cfg';
  $cfg = new Config_Lite("$file");
  $cfg->setQuoteStrings(True);
  $cfg->set(null,"OPTIONS",$level);
  $cfg->save();
}
?>

