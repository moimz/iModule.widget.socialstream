/**
 * 이 파일은 소셜스트림 위젯의 일부입니다. (https://www.imodules.io)
 * 
 * 소셜스트림 위젯 화면 UI 이벤트를 처리한다.
 *
 * @file /widgets/socialstream/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 10. 15.
 */
$(document).ready(function() {
	(function() {
		/**
		 * 위젯의 경로를 파악한다.
		 */
		var $me = $("script[src*='/widgets/socialstream/scripts/script.js']");
		var url = $me.attr("src").split("/scripts/script.js").shift();
		
		/**
		 * 소셜스트림 객체를 찾는다.
		 */
		$("div[data-widget=socialstream] > div[id]").each(function() {
			var $parent = $(this);
			$.send(url+"/process/index.php",{
				accounts:$(this).attr("data-accounts"),
				facebook:$(this).attr("data-facebook"),
				twitter:$(this).attr("data-twitter"),
				count:$(this).attr("data-count"),
				cache:$(this).attr("data-cache")
			},function(result) {
				if (result.success == true) {
					$("div[data-role=loading]",$parent).remove();
					for (var i=0, loop=result.lists.length;i<loop;i++) {
						var data = result.lists[i];
						
						var $item = $("<div>").addClass("item").addClass(data.type);
						var $box = $("<div>").addClass("box");
						
						var $image = $("<a>").addClass("image").attr("href",data.link);
						if (data.image) {
							$image.append($("<img>").attr("src",data.image));
							$image.css("backgroundImage","url("+data.image+")");
							$image.css("backgroundSize","cover");
						}
						$box.append($image);
						
						var $content = $("<div>").addClass("content").html(data.content);
						$box.append($content);
						
						var $account = $("<div>").addClass("account");
						if (data.type == "facebook") $account.append($("<i>").addClass("fa fa-facebook"));
						if (data.type == "twitter") $account.append($("<i>").addClass("fa fa-twitter"));
						
						$account.append($("<img>").addClass("photo").attr("src",data.photo));
						
						var $name = $("<div>").addClass("name").append($("<a>").attr("href",data.account).html(data.name));
						$account.append($name);
						
						var $detail = $("<a>").attr("href",data.link);
						$detail.append($("<span>").attr("data-time",data.time).addClass("time"));
						
						if (data.type == "facebook") {
							$detail.append($("<span>").addClass("count").html('<i class="fa fa-thumbs-o-up"></i> '+data.likes));
							$detail.append($("<span>").addClass("count").html('<i class="fa fa-comment-o"></i> '+data.comments));
						}
						
						if (data.type == "twitter") {
							$detail.append($("<span>").addClass("count").html('<i class="fa fa-retweet"></i> '+data.retweets));
							$detail.append($("<span>").addClass("count").html('<i class="fa fa-heart-o"></i> '+data.favorites));
						}
						
						$account.append($("<div>").addClass("detail").append($detail));
						
						$box.append($account);
						
						$item.append($box);
						$parent.append($item);
					}
				}
				
				$("span[data-time]",$parent).each(function() {
					moment.locale(ENV.LANGUAGE);
					$(this).html(moment(parseInt($(this).attr("data-time"))*1000).fromNow());
				});
				
				$("a[href]",$parent).on("click",function(e) {
					window.open($(this).attr("href"));
					e.preventDefault();
				});
				
				var $socialstream = $parent.isotope({
					itemSelector:"div.item",
					percentPosition:true,
					masonry:{
						isFitWidth:false
					}
				});
				
				$socialstream.imagesLoaded().progress(function() {
					$socialstream.isotope("layout");
				});
			})
		});
	})();
});