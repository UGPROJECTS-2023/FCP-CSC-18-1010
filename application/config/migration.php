<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Enable/Disable Migrations
| Migrations are disabled by default for security reasons.
*/
$config['migration_enabled'] = FALSE;

/*
| Migration Type
| Migration file names may be based on a sequential identifier or on
| a timestamp. 
*/
$config['migration_type'] = 'timestamp';

/*
| Migrations table
|
| This is the name of the table that will store the current migrations state.
*/
$config['migration_table'] = 'migrations';

/*
| Auto Migrate To Latest

| If this is set to TRUE when you load the migrations class and have
| $config['migration_enabled'] set to TRUE the system will auto migrate
| to your latest migration
*/
$config['migration_auto_latest'] = FALSE;

/*

| Migrations version

|
| This is used to set migration version that the file system should be on.
*/
$config['migration_version'] = 0;

/*
| Migrations Path
|
| Path to your migrations folder.
|
*/
$config['migration_path'] = APPPATH.'migrations/';
