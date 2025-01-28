window.onload = function() {
    // Disable right-click
    const bodyElement = document.body;
    const regNo = bodyElement.getAttribute('data-rollno');
    const quizId = bodyElement.getAttribute('data-quizid');

    console.log("Roll Number:", regNo);
    console.log("Quiz ID:", quizId);

    document.addEventListener("contextmenu", function(e) {
        e.preventDefault();
    }, false);

    document.getElementById('agreebut').addEventListener('click', function () {
        var keyCode, time;

        // window.addEventListener('offline', function() {
        //     console.log("Network is offline.");
        //     showExitModal();
        //     // Handle actions for when the network goes offline
        // });
        document.addEventListener("keydown", function(e) {
            keyCode = e.keyCode;
            time = new Date().getTime();
            // Allow letters (A-Z, a-z), numbers (0-9), and Enter key (keyCode 13)
            // if (
            //     (e.keyCode >= 65 && e.keyCode <= 90) ||  // A-Z
            //     (e.keyCode >= 48 && e.keyCode <= 57) ||  // 0-9 (main keyboard)-+
            //     (e.keyCode >= 96 && e.keyCode <= 105) || // 0-9 (numpad)
            //     e.keyCode === 13 ||  // Enter key
            //     e.keyCode === 32 ||  // Space key
            //     e.keyCode === 16 ||  // Shift key
            //     e.keyCode === 20 ||  // Caps Lock key
            //     (e.keyCode >= 37 && e.keyCode <= 40) ||
            //     (e.keyCode >= 186 && e.keyCode <= 192) || // ;=,-./` (punctuations)
            //     (e.keyCode >= 219 && e.keyCode <= 222)    // [\]' (punctuations)

            // ) {
            //     return;  // Allow these keys
            // }

            console.log("key pressed:", e.keyCode);
            
            // Call the modal popup for any other key pressed
            //showExitModal(e.keyCode);

            // Disable Ctrl, Alt, Shift, Meta (Windows/Command) keys
            if (e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) { 
                e.preventDefault();
            }

            // Disable specific key combinations (Ctrl + Tab, Shift + Tab)
            if ((e.ctrlKey && e.key === "Tab") || (e.shiftKey && e.key === "Tab")) {
                e.preventDefault();
                console.log("Ctrl + Tab or Shift + Tab detected");
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
                logUnwantedKey();
                showExitModal();
            }

            // Prevent Escape key (keyCode 27) and force fullscreen
            if (e.keyCode === 27) {
                e.preventDefault();
                enterFullscreen();
                handleFullscreenChange();        
            }
        }, false);

        function showExitModal() {
            logUnwantedKey();
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

        window.addEventListener('blur', function () {
            console.log('Window lost focus! Likely due to Alt+Tab or switching tabs.');
            showExitModal();
        });
        
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                console.log('Document is now hidden. Possible tab switch or Alt+Tab.');
                showExitModal();
            }
        });

        document.addEventListener("fullscreenchange", handleFullscreenChange);
        document.addEventListener("mozfullscreenchange", handleFullscreenChange);
        document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
        document.addEventListener("msfullscreenchange", handleFullscreenChange);

        function logUnwantedKey() {
            console.log('Roll Number:', regNo);
            console.log('Quiz ID:', quizId);
            console.log("KEY CODE: ",keyCode, "TIME: ", time);

            if(new Date().getTime() - time < 5000 && (keyCode === 91 || keyCode === 92 || keyCode === 27 || keyCode ===18 )) { 
                fetch('logKey.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `key=${keyCode}&regNo=${regNo}&quizId=${quizId}`,
                })
                    .then(response => response.text())
                    .then(data => console.log(data))
                    .catch(error => console.error('Error logging key:', error));
            }
        
        }
    });
};
