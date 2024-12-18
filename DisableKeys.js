
window.onload = function() {
    // Disable right-click
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    }, false);

    function disabledEvent(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        } else if (window.event) {
            window.event.cancelBubble = true;
        }
        e.preventDefault();
        return false;
    }

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

    document.addEventListener("keydown", function(e) {
        if (e.keyCode === 27) { // Esc key
            e.preventDefault();
            disabledEvent(e);
            enterFullscreen();
            handleFullscreenChange();
        } else {
            disabledEvent(e);
        }
    }, false);



    const noButton = document.getElementById('no');
    noButton.addEventListener('click', () => {
        closeModal();
        enterFullscreen();
    });


    const yesButton = document.getElementById('yes');
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