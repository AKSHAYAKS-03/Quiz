
window.onload = function() {
    // Disable right-click
    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    }, false);

    document.addEventListener("keydown", function(e) {
        // Disable Ctrl key completely
        if (e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) { 
            e.preventDefault();
        }

        // Disable specific key combinations (Ctrl + Tab, Shift + Tab)
        if ((e.ctrlKey && e.key === "Tab") || (e.shiftKey && e.key === "Tab")) {
            e.preventDefault();
        }
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
        
        if (e.keyCode === 27) { // Esc key
            e.preventDefault();
            enterFullscreen();
            handleFullscreenChange();        
        }
    }, false);

    // function disabledEvent(e) {
    //     if (e.stopPropagation) {
    //         e.stopPropagation();
    //     } else if (window.event) {
    //         window.event.cancelBubble = true;
    //     }
    //     e.preventDefault();
    //     return false;
    // }

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

    // function handleFullscreenChange() {
    //     enterFullscreen();
    //     if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
    //             var modal = document.getElementById('QuizModal');
    //             modal.style.display = 'block'; 
    //             setTimeout(() => {
    //                 modal.querySelector('.modal-content').classList.add('show-modal'); 
    //             }, 10);
    //     }
    // }

    function handleFullscreenChange() {
        // Check if fullscreen is exited
        if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
            // Show the warning modal
            var modal = document.getElementById('QuizModal');
            modal.style.display = 'block';
            
            // Add the "show-modal" class for animation or styling
            setTimeout(() => {
                modal.querySelector('.modal-content').classList.add('show-modal'); 
            }, 10);
            
            // After 5 seconds, redirect to final.php
            setTimeout(() => {
                window.location.href = 'final.php'; 
            }, 3000);  // 5 seconds delay
        }
    }
    

    document.addEventListener("fullscreenchange", handleFullscreenChange);
    document.addEventListener("mozfullscreenchange", handleFullscreenChange);
    document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
    document.addEventListener("msfullscreenchange", handleFullscreenChange);

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