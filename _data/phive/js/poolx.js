let scrollMessageCount = 0;

const getIframeElement = () => document.getElementById('poolx');

const getNavbarHeight = () => {
    if (isMobile()) {
        return document.getElementById('mobile-top').offsetHeight;
    }

    return document.getElementById('rg-top-bar').offsetHeight;
}

const resizeIframe = (height) => {
    const iframe = getIframeElement();
    iframe.height = height;
}

const handleScrollToTop = () => {
    scrollMessageCount += 1;

    /** Hack to ignore first two messages - avoid scrolling on page load. */
    if (scrollMessageCount <= 2) {
        return;
    }

    const iframe = getIframeElement();

    /* Avoid covering top of iframe by navbar */
    const scrollToY = iframe.offsetTop - getNavbarHeight();

    window.scroll({ behavior: 'smooth', top: scrollToY });
}

const handleMessage = ({ data }) => {
    if (data.type === 'content_resize') {
        resizeIframe(data.height + data.units);
        return;
    }

    if (data === 'scroll_to_top') {
        handleScrollToTop();
    }
}

window.addEventListener('message', handleMessage);