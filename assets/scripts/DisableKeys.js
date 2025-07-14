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

        document.addEventListener("keydown", function(e) {
            keyCode = e.keyCode;
            time = new Date().getTime();
            console.log(keyCode);

            console.log("key pressed:", e.keyCode);

            if (e.key === 'Alt') {
                showExitModal();
            }

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
                showExitModal();
            }

        }, false);

        function showExitModal() {
            logUnwantedKey();
            var modal = document.getElementById('QuizModal');
            modal.style.display = 'block';

            setTimeout(() => {
                modal.querySelector('.modal-content').classList.add('show-modal'); 
            }, 10);
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

        function logUnwantedKey() {
            console.log('Roll Number:', regNo);
            console.log('Quiz ID:', quizId);
            console.log("KEY CODE: ",keyCode, "TIME: ", time);

            if((keyCode !== 91 && keyCode !== 92 && keyCode!==18)){
                keyCode = 27;
            }
            console.log("here is the code", keyCode);
            console.log("KEY CODE: ",keyCode, "TIME: ", time);
            console.log('Roll Number:', regNo);
            fetch('../../auth/logKey.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `key=${keyCode}&regNo=${regNo}&quizId=${quizId}`,
            })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    // Now safely redirect
                     window.location.href = '../../Quiz/final.php';
                })
                .catch(error => {
                    console.error('Error logging key:', error);
                    // Even if there's an error, still redirect
                    // window.location.href = 'final.php';
                });
        }
    });
};