
// Active page menu when click ( only for demo )
var url = window.location.href;
var $nav_link = $(".dropdown li a");
$nav_link.each(function () {
	if (url === (this.href)) {
		$(this).closest("li").addClass("active");
	}
});


// 1. This code loads the IFrame Player API code asynchronously.
var tag = document.createElement('script');

tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// 2. This function creates an <iframe> (and YouTube player)
//    after the API code downloads.
var player;
function onYouTubeIframeAPIReady() {
	player = new YT.Player('player', {
		height: '100%',
		width: '100%',
		playerVars: {
			autoplay: 1,
			loop: 1,
			mute: 1,
			rel: 0,
			controls: 0,
			showinfo: 0,
			autohide: 1,
			modestbranding: 1,
			playlist: 'PLbCCiSfsOKf9msL0pG0FZCuE0HwmKvkrb',
			vq: 'hd1080'
		},
		videoId: 'GRAXRqXgCK0',
		events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		}
	});
}

// 3. The API will call this function when the video player is ready.
function onPlayerReady(event) {
	event.target.playVideo();
	player.mute();
}

var done = false;
function onPlayerStateChange(event) {
	event.target.playVideo();
}
