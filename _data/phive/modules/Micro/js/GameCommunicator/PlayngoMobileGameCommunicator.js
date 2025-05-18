$(document).ready(function () {
    var iframe = document.getElementById('vs-game-container__iframe-1');
    iframe.onload = function () {
        this.contentWindow.postMessage({messageType: "addEventListener", eventType: 'logout'}, '*');
    };

    window.addEventListener('message', function (ev) {
        if (ev.data.type === 'logout') {
            goTo(llink('/'));
        }
    });
});