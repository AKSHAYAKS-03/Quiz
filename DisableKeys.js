window.onload = function() {
    // Disable right-click
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    }, false);

    document.addEventListener("keydown", function(e) {
        // Allow letters (A-Z, a-z), numbers (0-9), and Enter key (keyCode 13)
        if (
            (e.keyCode >= 65 && e.keyCode <= 90) ||  // A-Z
            (e.keyCode >= 48 && e.keyCode <= 57) ||  // 0-9 (main keyboard)
            (e.keyCode >= 96 && e.keyCode <= 105) || // 0-9 (numpad)
            e.keyCode === 13  // Enter key
        ) {
            return;  // Allow these keys
        }

        // Call the modal popup for any other key pressed
        showExitModal();

        // Disable Ctrl, Alt, Shift, Meta (Windows/Command) keys
        if (e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) { 
            e.preventDefault();
        }

        // Disable specific key combinations (Ctrl + Tab, Shift + Tab)
        if ((e.ctrlKey && e.key === "Tab") || (e.shiftKey && e.key === "Tab")) {
            e.preventDefault();
        }

        // Disable Home key (keyCode 36)
        if (e.keyCode === 36) {
            e.preventDefault();
        }

        // Disable Print Screen key (keyCode 44)
        if (e.keyCode === 44) {
            e.preventDefault();
        }

        // Disable Windows key (keyCode 91 or 92)
        if (e.keyCode === 91 || e.keyCode === 92) {
            e.preventDefault();
            handleFullscreenChange();        
        }

        // Prevent Escape key (keyCode 27) and force fullscreen
        if (e.keyCode === 27) {
            e.preventDefault();
            enterFullscreen();
            handleFullscreenChange();        
        }
    }, false);

    function showExitModal() {
        var modal = document.getElementById('QuizModal');
        modal.style.display = 'block';

        setTimeout(() => {
            modal.querySelector('.modal-content').classList.add('show-modal'); 
        }, 10);

        // After 3 seconds, redirect to final.php
        setTimeout(() => {
            window.location.href = 'final.php'; 
        }, 3000);
    }

    function enterFullscreen() {
        var elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    }

    function handleFullscreenChange() {
        if (!document.fullscreenElement && !document.mozFullScreenElement && 
            !document.webkitFullscreenElement && !document.msFullscreenElement) {
            showExitModal();
        }
    }

    document.addEventListener("fullscreenchange", handleFullscreenChange);
    document.addEventListener("mozfullscreenchange", handleFullscreenChange);
    document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
    document.addEventListener("msfullscreenchange", handleFullscreenChange);

    document.getElementById('no').addEventListener('click', () => {
        closeModal();
        enterFullscreen();
    });

    document.getElementById('yes').addEventListener('click', () => {
        closeModal();
        window.location.href = 'final.php'; 
    });

    function closeModal() {
        document.querySelector('.modal-content').classList.remove('show-modal'); 
        setTimeout(() => {
            document.getElementById('QuizModal').style.display = 'none';
        }, 500); 
    }
};
