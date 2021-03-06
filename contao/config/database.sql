-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the Contao    *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

CREATE TABLE `tl_metamodel_translatedpageid` (

  `att_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `langcode` varchar(5) NOT NULL,

  `tstamp` int(10) unsigned NOT NULL,
  `value_id` int(10) unsigned NOT NULL,

  PRIMARY KEY  (`att_id`, `item_id`, `langcode`),
  KEY `att_lang` (`att_id`, `langcode`),
  
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
