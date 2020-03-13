<?php
/**
 * 이 파일은 소셜스트림 위젯의 일부입니다. (https://www.imodules.io)
 * 
 * @file /widgets/socialstream/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 3. 13.
 */
if (defined('__IM__') == false) exit;

$IM->addHeadResource('script',$Widget->getDir().'/scripts/imagesloaded.min.js');
$IM->addHeadResource('script',$Widget->getDir().'/scripts/isotope.min.js');
$IM->addHeadResource('script',$Widget->getDir().'/scripts/script.js');
$IM->loadWebFont('FontAwesome');

$count = $Widget->getValue('count');
$cache = $Widget->getValue('cache');
$facebooks = $Widget->getValue('facebook') ? explode(',',$Widget->getValue('facebook')) : array();
$twitters = $Widget->getValue('twitter') ? explode(',',$Widget->getValue('twitter')) : array();

$facebook_token = $Widget->getValue('facebook_app_id') && $Widget->getValue('facebook_app_secret') ? $Widget->getValue('facebook_app_id').'|'.$Widget->getValue('facebook_app_secret') : null;
$facebook_token = $facebook_token == null ? $Widget->getValue('facebook_access_token') : $facebook_token;
$twitter_token = $Widget->getValue('twitter_consumer_key') && $Widget->getValue('twitter_consumer_secret') && $Widget->getValue('twitter_access_token') && $Widget->getValue('twitter_access_token_secret') ? json_encode(array($Widget->getValue('twitter_consumer_key'),$Widget->getValue('twitter_consumer_secret'),$Widget->getValue('twitter_access_token'),$Widget->getValue('twitter_access_token_secret'))) : null;

if (count($facebooks) > 0 && $facebook_token == null) return $Widget->getError('REQUIRED_FACEBOOK_TOKEN','facebook_app_id, facebook_app_secret');
if (count($twitters) > 0 && $twitter_token == null) return $Widget->getError('REQUIRED_TWITTER_TOKEN','twitter_consumer_key, twitter_consumer_secret, twitter_access_token, twitter_access_token_secret');

$accounts = array();
foreach ($facebooks as $facebook) $accounts[] = 'facebook-'.$facebook;
foreach ($twitters as $twitter) $accounts[] = 'twitter-'.$twitter;

if (count($accounts) == 0) return $Widget->getError('NOT_EXISTS_ACCOUNT');

$header = '<div id="'.$Widget->getRandomId().'" data-accounts="'.implode(',',$accounts).'" data-count="'.$count.'" data-cache="'.$cache.'" data-facebook="'.($facebook_token ? Encoder($facebook_token) : '').'" data-twitter="'.($twitter_token ? Encoder($twitter_token) : '').'">'.PHP_EOL;

$footer = '</div>'.PHP_EOL;

return $Templet->getContext('index',get_defined_vars(),$header,$footer);
?>