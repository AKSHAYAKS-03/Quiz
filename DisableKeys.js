
window.onload = function() {
    // Disable right-click
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    }, false);

    // Function to disable event
    function disabledEvent(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        } else if (window.event) {
            window.event.cancelBubble = true;
        }
        e.preventDefault();
        return false;
    }

    // Function to request full-screen mode
    function enterFullscreen() {
        var elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { // Firefox
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { // Chrome, Safari and Opera
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { // IE/Edge
            elem.msRequestFullscreen();
        }
    }

    // Monitor for full-screen exit and re-enter full-screen mode
    function handleFullscreenChange() {
        if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
            if (confirm("Are you sure you want to exit the quiz? You are not allowed to re-take the quiz again.")) {
                // Redirect to the quiz exit page or perform any exit-related action
                window.location.href = 'final.php'; // Change this to your actual exit page URL
            } else {
                // Re-enter full-screen if the user cancels the exit
                enterFullscreen();
            }
        }
    }

    document.addEventListener("fullscreenchange", handleFullscreenChange);
    document.addEventListener("mozfullscreenchange", handleFullscreenChange);
    document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
    document.addEventListener("msfullscreenchange", handleFullscreenChange);

    // Disable all keys except for certain cases like Esc
    document.addEventListener("keydown", function(e) {
        if (e.keyCode === 27) { // Esc key
            // Prevent default Esc key behavior
            e.preventDefault();
        } else {
            // Prevent default behavior for all other keys
            disabledEvent(e);
        }
    }, false);

    // Initially enter full-screen mode
    enterFullscreen();
};