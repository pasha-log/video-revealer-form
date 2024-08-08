/**
 * YouTube API
 */

var youTubePlayer;

function onYouTubeIframeAPIReady() {
	if (typeof YT !== 'undefined' && YT && YT.Player) {
		initializeYouTubePlayer();
	} else {
		console.error('YT.Player is not available');
	}
}

function initializeYouTubePlayer() {
	youTubePlayer = new YT.Player('youtube-video', {
		playerVars: {
			playsinline: 1,
			origin: window.location.origin
		},
		events: {
			onReady: onPlayerReady,
			onStateChange: onPlayerStateChange
		}
	});
}

function onPlayerReady(event) {
	console.log('Player is ready');
}

function onPlayerStateChange(event) {
	if (event.data == YT.PlayerState.ENDED) {
		console.log('Video ended');
		$('#finished-video-button-container').css('display', 'flex');
	} else if (event.data == YT.PlayerState.PLAYING) {
		console.log('Video started playing');
	}
}

var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutation) {
		if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
			var videoContainer = $('#video-container');
			if (videoContainer.css('display') === 'flex') {
				initializeYouTubePlayer();
				observer.disconnect();
			}
		}
	});
});

var videoContainer = document.getElementById('video-container');
if (videoContainer) {
	observer.observe(videoContainer, { attributes: true });
}
