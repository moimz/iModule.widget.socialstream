<?php
/**
 * 이 파일은 소셜스트림 위젯의 일부입니다. (https://www.imodule.kr)
 * 
 * 소셜네트워크 API 를 이용하여 최근 게시물을 가져온다.
 * iModule 규칙에 맞지 않는 파일위치이므로 직접 init.config.php 을 호출하여 iModule 코어클래스를 정의한다.
 *
 * @file /widgets/socialstream/index.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0.161001
 */

REQUIRE_ONCE '../../../configs/init.config.php';

$IM = new iModule();

$accounts = Request('accounts') ? explode(',',Request('accounts')) : array();
$cache = Request('cache');
$count = Request('count');
$facebook = Request('facebook') ? Decoder(Request('facebook')) : false;
$twitter = Request('twitter') ? json_decode(Decoder(Request('twitter'))) : false;

/**
 * iModule 코어클래스로부터 나 자신을 정의한다.
 */
$me = $IM->getWidget('socialstream');

if ($twitter !== false) REQUIRE_ONCE $me->getPath().'/classes/TwitterOAuth.class.php';

/**
 * 캐시를 생성하기 위해 넘어온 설정값을 지정한다.
 */
$me->setValue('accounts',$accounts)->setValue('cache',$cache)->setValue('count',$count);

/**
 * Facebook 게시물을 가져오기 위한 함수
 */
function GetSocailStreamFacebook($id) {
	global $facebook, $count;
	
	if ($facebook === false) return null;
	
	$fields = "id,message,picture,link,name,description,created_time,from,object_id,likes.summary(true),comments.summary(true),attachments{media{image}}";
	
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,'https://graph.facebook.com/v2.8/'.$id.'/posts?access_token='.$facebook.'&fields='.$fields.'&limit='.$count);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	
	$result = curl_exec($ch);
	$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	$content_type = explode(';',curl_getinfo($ch,CURLINFO_CONTENT_TYPE));
	$content_type = array_shift($content_type);
	
	if ($http_code == 200) {
		$result = json_decode($result);
		$data = $result->data;
		
		$lists = array();
		for ($i=0, $loop=count($data);$i<$loop;$i++) {
			if (isset($data[$i]->link) == false) continue;
			
			$item = new stdClass();
			$item->id = $data[$i]->id;
			$item->type = 'facebook';
			$item->content = isset($data[$i]->message) == true ? $data[$i]->message.' ' : '';
			$item->content = AutoLink($item->content);
			$item->content = preg_replace('/\#([^[:space:]#]+)/','<a href="https://www.facebook.com/hashtag/\1">\0</a> ',$item->content);
			$item->content = nl2br(trim($item->content));
			
			$item->link = $data[$i]->link;
			$item->time = strtotime($data[$i]->created_time);
			$item->name = $data[$i]->from->name;
			$item->account = 'https://www.facebook.com/'.$id;
			$item->photo = 'http://graph.facebook.com/'.$data[$i]->from->id.'/picture?type=normal';
			$item->likes = $data[$i]->likes->summary->total_count;
			$item->comments = $data[$i]->comments->summary->total_count;
			
			if (isset($data[$i]->attachments) == true && count($data[$i]->attachments->data) > 0) {
				foreach ($data[$i]->attachments->data as $attachment) {
					if (isset($attachment->media) == true && isset($attachment->media->image) == true) {
						$item->image = $attachment->media->image->src;
						break;
					}
				}
			}
			
			$lists[] = $item;
		}
		
		return $lists;
	} else {
		return null;
	}
	
	return $result;
}

/**
 * 트위터 게시물을 가져오기 위한 함수
 */
function GetSocailStreamTwitter($id) {
	global $twitter, $count;
	
	if ($twitter === false || is_array($twitter) === false) return null;
	
	$auth = new TwitterOAuth($twitter[0],$twitter[1],$twitter[2],$twitter[3]);
	$get = $auth->get('statuses/user_timeline',array('count'=>$count,'screen_name'=>$id));
	
	if ($get && isset($get->errors) == false) {
		$data = json_decode($get);
		
		$lists = array();
		for ($i=0, $loop=count($data);$i<$loop;$i++) {
			$lists[$i] = new stdClass();
			$lists[$i]->id = $data[$i]->id;
			$lists[$i]->type = 'twitter';
			$lists[$i]->content = isset($data[$i]->text) == true ? $data[$i]->text.' ' : '';
			$lists[$i]->content = AutoLink($lists[$i]->content);
			$lists[$i]->content = preg_replace('/\#([^[:space:]#]+)/','<a href="https://twitter.com/hashtag/\1">\0</a> ',$lists[$i]->content);
			$lists[$i]->content = nl2br(trim($lists[$i]->content));
			
			$lists[$i]->link = 'https://twitter.com/arzzcom/status/'.$data[$i]->id;
			$lists[$i]->time = strtotime($data[$i]->created_at);
			$lists[$i]->name = $data[$i]->user->name;
			$lists[$i]->account = 'https://www.twitter.com/'.$data[$i]->user->screen_name;
			$lists[$i]->photo = str_replace('_normal.','_400x400.',$data[$i]->user->profile_image_url_https);
			$lists[$i]->retweets = $data[$i]->retweet_count;
			$lists[$i]->favorites = $data[$i]->favorite_count;
			
			if (isset($data[$i]->entities) == true && isset($data[$i]->entities->media) == true) {
				foreach ($data[$i]->entities->media as $attachment) {
					if ($attachment->type == 'photo') {
						$lists[$i]->image = $attachment->media_url_https;
						break;
					}
				}
			}
		}
		
		return $lists;
	} else {
		return null;
	}
	
	return $result;
}

if (true || $me->checkCache() < time() - $cache) {
	$rawLists = array();
	for ($i=0, $loop=count($accounts);$i<$loop;$i++) {
		$temp = explode('-',$accounts[$i]);
		$type = array_shift($temp);
		$id = implode('-',$temp);
		
		$feed = $type == 'facebook' ? GetSocailStreamFacebook($id) : GetSocailStreamTwitter($id);
		if ($feed !== null && count($feed) > 0) $rawLists = array_merge($rawLists,$feed);
	}
	
	$sortLists = array();
	for ($i=0, $loop=count($rawLists);$i<$loop;$i++) {
		$sortLists[$rawLists[$i]->time.'-'.$rawLists[$i]->id] = $rawLists[$i];
	}
	krsort($sortLists);
	
	$lists = array();
	foreach ($sortLists as $data) {
		$lists[] = $data;
		
		if (count($lists) == $count) break;
	}
	
	$me->storeCache(json_encode($lists,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
} else {
	$data = $me->getCache();
	$lists = json_decode($data);
}

header('Content-type:text/json; charset=utf-8',true);
header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control:post-check=0, pre-check=0', false);
header('Pragma:no-cache');

$results = new stdClass();
$results->success = true;
$results->lists = $lists;
exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
?>