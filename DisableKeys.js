
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
        enterFullscreen();
        if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                var modal = document.getElementById('QuizModal');
                modal.style.display = 'block'; 
                setTimeout(() => {
                    modal.querySelector('.modal-content').classList.add('show-modal'); 
                }, 10);
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
            disabledEvent(e);
            enterFullscreen();
            handleFullscreenChange();
        } else {
            // Prevent default behavior for all other keys
            disabledEvent(e);
        }
    }, false);



    // Get the button element by its ID
    const noButton = document.getElementById('no');
    // Add an event listener to the button for the 'click' event
    noButton.addEventListener('click', () => {
        closeModal();
        enterFullscreen();
    });


    const yesButton = document.getElementById('yes');
    // Add an event listener to the button for the 'click' event
    yesButton.addEventListener('click', () => {
        closeModal();
        window.location.href = 'final.php'; 
    });


    function closeModal() {
        document.querySelector('.modal-content').classList.remove('show-modal'); 
        setTimeout(() => {
            document.getElementById('QuizModal').style.display = 'none';
        }, 500); 
    }

}