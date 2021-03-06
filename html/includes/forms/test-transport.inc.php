<?php

/*
 * LibreNMS
 *
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk/fa>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

use LibreNMS\Authentication\Auth;

if (!Auth::user()->hasGlobalAdmin()) {
    header('Content-type: text/plain');
    die('ERROR: You need to be admin');
}

$transport = $vars['transport'] ?: null;
$transport_id = $vars['transport_id'] ?: null;

require_once $config['install_dir'].'/includes/alerts.inc.php';
$tmp = array(dbFetchRow('select device_id,hostname,sysDescr,version,hardware,location from devices order by device_id asc limit 1'));
$tmp['contacts'] = GetContacts($tmp);
$obj = array(
    "hostname"  => $tmp[0]['hostname'],
    "device_id" => $tmp[0]['device_id'],
    "sysDescr" => $tmp[0]['sysDescr'],
    "version" => $tmp[0]['version'],
    "hardware" => $tmp[0]['hardware'],
    "location" => $tmp[0]['location'],
    "title"     => "Testing transport from ".$config['project_name'],
    "elapsed"   => "11s",
    "id"        => "000",
    "faults"    => false,
    "uid"       => "000",
    "severity"  => "critical",
    "rule"      => "macros.device = 1",
    "name"      => "Test-Rule",
    "string"      => "#1: test => string;",
    "timestamp" => date("Y-m-d H:i:s"),
    "contacts"  => $tmp['contacts'],
    "state"     => "1",
    "msg"       => "This is a test alert",
);

$status = 'error';

if ($transport_id) {
    $transport = dbFetchCell("SELECT `transport_type` FROM `alert_transports` WHERE `transport_id` = ?", [$transport_id]);
}
$class  = 'LibreNMS\\Alert\\Transport\\' . ucfirst($transport);
if (class_exists($class)) {
    $opts = $config['alert']['transports'][$transport];
    $instance = new $class($transport_id);
    $tmp = $instance->deliverAlert($obj, $opts);
    if ($tmp) {
        $status = 'ok';
    }
}
header('Content-type: application/json');
echo _json_encode(array('status' => $status));
