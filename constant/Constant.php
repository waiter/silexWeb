<?php
/**
 * Created by PhpStorm.
 * User: waiter
 * Date: 16/2/6
 * Time: 下午12:41
 */

class Constant
{
    const DB_CLOUD_ATLAS = 'CloudAtlas';
    const DB_CLOUD_BLACK = 'CloudBlack';
    const DB_USER = 'User';
    const DB_RANK_GAME = 'RankGame';
    const DB_RANK_LIST = 'RankList';
    const DB_RANK_DATA = 'RankData';
    const DB_CONFIG_GAME = 'ConfigGame';
    const DB_CONFIG_DATA = 'ConfigData';

    const CACHE_CLOUD_ATLAS_ALL = 'cacheCAAll';
    const CACHE_CLOUD_ATLAS_LIST = 'cacheCAList';
    const CACHE_CLOUD_ATLAS_PRE = 'cacheCAPre_';
    const CACHE_CLOUD_BLACK_ALL = 'cacheCBAll';
    const CACHE_RANK_GAME_ALL = 'cacheRGAll';
    const CACHE_RANK_LIST_ALL_PRE = 'cacheRLAPre_';
    const CACHE_RANK_LIST_INFO_PRE = 'cacheRLIPre_';
    const CACHE_RANK_DATA_PRE = 'cacheRDPre_';
    const CACHE_CONFIG_GAME_ALL = 'cacheCGAll';
    const CACHE_CONFIG_DATA_PRE = 'cacheCDPre_';

    const DAY_TIME = 86400;
    const WEEK_TIME = 604800;

    public static $RankType = array('A', 'D', 'W', 'M');
}