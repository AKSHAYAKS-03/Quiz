window.onload = function() {
    document.addEventListener("contextmenu", function(e) {
      e.preventDefault();
    }, false);
  
    document.addEventListener("keydown", function(e) {
      if (e.ctrlKey && e.shiftKey && e.keyCode == 73) { // Ctrl+Shift+I
        disabledEvent(e);
      }
      if (e.ctrlKey && e.shiftKey && e.keyCode == 74) { // Ctrl+Shift+J
        disabledEvent(e);
      }
      if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) { // Ctrl+S / Cmd+S
        disabledEvent(e);
      }
      if (e.ctrlKey && e.keyCode == 85) { // Ctrl+U
        disabledEvent(e);
      }
      if (e.keyCode == 123) { // F12
        disabledEvent(e); 
      }
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
  };
  