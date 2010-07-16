<?php
print "\n\n\n\n\n";
print "--------------------------\n";
// Playlist generator for retro tv.
// gets content from database. 
// script can also index content.
// knows a few slots and has time settings for each slot
// works with well known tv shows
// This has to do with presentation and content: timeslot formatting.
// during cartoon hour we don't want porn commercials. WE WANT ACTION MAN!
define("TIMESLOT_CARTOON", "cartoon");
define("TIMESLOT_MOVIE", "movie");
define("TIMESLOT_SERIE", "serie"); 
// default timeslot is called "other"

// directory configuration.. since its getting out of hand
$path = array();
$path['physicalroot'] = "/Users/stitch/Documents/tv/";
$path['scripts']  = $path['physicalroot']."scripts/"; // dir
$path['database'] = $path['physicalroot']."content/content.db"; // file
$path['content']  = $path['physicalroot']."content/"; // dir
$path['announcements']   = $path['physicalroot']."content/announcements/";
$path['design']   = $path['physicalroot']."content/announcements/source/"; 

// http://www.kijkwijzer.nl
define("PARENTAL_SAFE", "0");
define("PARENTAL_ADVICE_AGE_6", "6");
define("PARENTAL_ADVICE_AGE_9", "9");
define("PARENTAL_ADVICE_AGE_12", "12");
define("PARENTAL_ADVICE_AGE_16", "16");
define("PARENTAL_CONTENT_VIOLENCE", "violence");
define("PARENTAL_CONTENT_FRIGHT", "fright");
define("PARENTAL_CONTENT_SEX", "sex");
define("PARENTAL_CONTENT_DISCRIMINATION", "discrimination");
define("PARENTAL_CONTENT_SUBSTANCE_ABUSE", "substance abuse");
define("PARENTAL_CONTENT_LANGUAGE", "language");

// maps to directories, definite list of all content directories
define("CONTENT_SHOW", "shows");
define("CONTENT_COMMERCIAL", "commercials");
define("CONTENT_BUMPER", "bumpers");
define("CONTENT_ANNOUNCEMENT", "announcements");
define("CONTENT_FUNNY", "funny movies");
define("CONTENT_MUSIC", "musicvideos");

define("BUMPER_COMMERCIAL_START", "Commercial Start");
define("BUMPER_COMMERCIAL_END", "Commercial End");
define("BUMPER_PROMO", "Promo");
// bumpers are short movies, no support for leaders (hourly) yet..

// actions from the command line, webinterface or any other method (voodoo)
define("ACTION_REINDEX", "Reindex");

define("ACTION_REINDEX", 2);

include("./tools/getid3/getid3/getid3.php");
$getid3 = new getid3();

// configure di
require($path['scripts']."code/dependancyInjector.php");
$dependancyInjector = dependancyInjector::instance();
$dependancyInjector->add($path['scripts']."code/");

// configure timezome
date_default_timezone_set("Europe/Berlin");

// php error handling
error_reporting(E_ALL);

// increase execution time, for announcements.
set_time_limit(2400);


// script configuration
$config["createAnnouncements"] = false;

// Handle both webinterface actions as command line options...
// same set of option, but the way to enable this is different.

$action = ACTION_REINDEX;
if ($action == ACTION_REINDEX) {
    $contentIndexer = new contentIndexer();
    $contentIndexer->indexContent();
}

// the name of your show has to match a directory, otherwhise the fallback dir "other" is used ...
// make sure timeslots match, otherwise the "other" directory is used...
// datum is nodig... en misschien een uur vanteovren de volgende playlist maken via CRONHELL. nee
// ik haat cron dus dat gaan we niet doen. 
$showSchedule = new showSchedule();
$showSchedule->addShow(TIMESLOT_SERIE,   "0600", "Penn & Tellers Bullshit!");
$showSchedule->addShow(TIMESLOT_CARTOON, "0700", "Kinder Theater"); // div filmpjes van 5 mins
$showSchedule->addShow(TIMESLOT_CARTOON, "0730", "Samson");
$showSchedule->addShow(TIMESLOT_CARTOON, "0800", "Teddy Ruxpin");
$showSchedule->addShow(TIMESLOT_CARTOON, "0830", "Ovide");
$showSchedule->addShow(TIMESLOT_CARTOON, "0900", "Bassie en Adriaan");
$showSchedule->addShow(TIMESLOT_CARTOON, "0930", "Around The Twist");
$showSchedule->addShow(TIMESLOT_SERIE,   "1000", "MythBusters");
$showSchedule->addShow(TIMESLOT_SERIE,   "1100", "AVGN");
$showSchedule->addShow(TIMESLOT_SERIE,   "1120", "Aqua Teenage Hunger Force");
$showSchedule->addShow(TIMESLOT_SERIE,   "1200", "Family Guy");
$showSchedule->addShow(TIMESLOT_SERIE,   "1230", "Drawn Together");
$showSchedule->addShow(TIMESLOT_SERIE,   "1300", "The A Team");
$showSchedule->addShow(TIMESLOT_SERIE,   "1400", "Knight Rider");
$showSchedule->addShow(TIMESLOT_SERIE,   "1500", "Kinder Theater");
$showSchedule->addShow(TIMESLOT_SERIE,   "1530", "Spongebob Squarepants");
$showSchedule->addShow(TIMESLOT_SERIE,   "1600", "The Legend of Prince Valiant");
$showSchedule->addShow(TIMESLOT_SERIE,   "1800", "Philosophy - A guide to Happiness");
$showSchedule->addShow(TIMESLOT_SERIE,   "2100", "Penn & Tellers Bullshit!");
$showSchedule->addShow(TIMESLOT_SERIE,   "2300", "Penn & Tellers Bullshit!");

// maybe render to VLC telnet...
$shows = $showSchedule->getShows();
$programmingCreator = new programmingCreator($shows);
//$programmingCreator->renderProgramToXSPF($path['physicalroot']."playlist".date("his").".xspf");
$programmingCreator->renderShowsToXSPF($path['physicalroot']);
//$programmingCreator->debug();

?>