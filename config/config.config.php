<?php
//initialize config variable used to store all configuration options
$config = array();

//database connection
$config['db']['type'] = 'mysql';
$config['db']['host'] = 'localhost';
$config['db']['port'] = '3306';
$config['db']['user'] = 'tracks';
$config['db']['password'] = 'password';
$config['db']['base'] = 'tracks';

$config['log']['file'] = 'logs'.DIRECTORY_SEPARATOR.'tracks.log';
$config['log']['file_level'] = 2;
$config['log']['db_level'] = 2;

$config['debug_style'] = 'background-color: white; color: black; font-family: monospace; font-size: 0.8rem; padding: 1rem;';
$config['debug']['db'] = false;
$config['debug']['http'] = false;

$config['start_page'] = 'Home';
$config['preserve_comment'] = true;
$config['preserve_cdata'] = true;
$config['preserve_space'] = false;
$config['format_output'] = false;
