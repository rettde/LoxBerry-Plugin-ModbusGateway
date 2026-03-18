<?php
//require_once "loxberry_system.php";
//require_once "loxberry_web.php";
require_once "Config/Lite.php";

function zmata_validate_device($device) {
  if (!preg_match('/^[a-zA-Z0-9._:-]+$/', $device)) {
    return false;
  }
  if (strpos($device, '..') !== false) {
    return false;
  }
  return true;
}

function zmata_sanitize_html($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

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

function zmata_system_port_used($port) {
  $p = intval($port);
  $out = shell_exec('ss -tlnH sport = :'. $p. ' 2>/dev/null');
  if ($out === null) {
    $out = shell_exec('netstat -tlnp 2>/dev/null | grep -E ":'. $p. '\b"');
  }
  return !empty(trim($out));
}

function zmata_next_port($confdir, $baseport = 502) {
  $usedports = array();
  $mask = $confdir. '/mbusd-*.conf';
  foreach (glob($mask) as $file) {
    try {
      $cfg = new Config_Lite("$file");
      $port = $cfg->get(null, "port");
      if ($port) {
        $usedports[] = intval($port);
      }
    } catch (Exception $e) {}
  }
  $port = $baseport;
  while (in_array($port, $usedports) || zmata_system_port_used($port)) {
    $port++;
    if ($port > 65535) return false;
  }
  return strval($port);
}

function zmata_port_in_use($confdir, $port, $excludedevice = null) {
  $mask = $confdir. '/mbusd-*.conf';
  foreach (glob($mask) as $file) {
    try {
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
    } catch (Exception $e) {}
  }
  if (zmata_system_port_used($port)) {
    return 'SYSTEM (another service)';
  }
  return false;
}

function zmata_device_in_use($confdir, $device) {
  $serialcfg = $confdir. '/mbusd.cfg';
  $scfg = new Config_Lite("$serialcfg");
  $serialpath = $scfg->get(null, "SERIAL");
  if (!$serialpath) {
    $serialpath = '/dev/serial/by-id/';
  }
  $devpath = $serialpath. $device;
  $out = shell_exec('fuser '. escapeshellarg($devpath). ' 2>/dev/null');
  if (!empty(trim($out))) {
    $pids = trim($out);
    $procs = array();
    foreach (explode(' ', $pids) as $pid) {
      $pid = trim($pid);
      if (!empty($pid) && is_numeric($pid)) {
        $name = trim(shell_exec('ps -p '. intval($pid). ' -o comm= 2>/dev/null'));
        if (!empty($name) && $name !== 'mbusd') {
          $procs[] = $name. ' (PID '. $pid. ')';
        }
      }
    }
    if (!empty($procs)) {
      return implode(', ', $procs);
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

