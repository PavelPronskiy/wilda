function timeAgo(input) {
    const date = (input instanceof Date) ? input : new Date(input);
    const formatter = new Intl.RelativeTimeFormat('ru');
    const ranges = {
        years: 3600 * 24 * 365,
        months: 3600 * 24 * 30,
        weeks: 3600 * 24 * 7,
        days: 3600 * 24,
        hours: 3600,
        minutes: 60,
        seconds: 1
    };
    const secondsElapsed = (date.getTime() - Date.now()) / 1000;

    for (const key in ranges) {
        if (ranges[key] < Math.abs(secondsElapsed)) {
            const delta = secondsElapsed / ranges[key];
            return formatter.format(Math.round(delta), key);
        }
    }
}

/**
 * { function_description }
 *
 * @param      {<type>}  url      The url
 * @param      {<type>}  success  The success
 */
function xhrGet(url, success) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", url);
    xhr.responseType = "json";
    xhr.onload = success;
    xhr.send();
}


/**
 * This class describes a wilda cleaner.
 */
class wildaCleaner extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
    }


    /**
     * { function_description }
     */
    connectedCallback() {
        const wrapElement = document.createElement("div");
        const wrapTwoColsElement = document.createElement("div");
        const wrapLeftColElement = document.createElement("div");
        const wrapRightColElement = document.createElement("div");
        
        const wrapPageHeaders = document.createElement("div");
        const twoColsPageHeaders = document.createElement("div");
        const lastModifiedPageHeaders = document.createElement("div");
        const datePageHeaders = document.createElement("div");

        const buttonElement = document.createElement("button");
        const settingsElement = document.createElement("button");
        wrapPageHeaders.setAttribute("class", "wrap-page-headers");
        twoColsPageHeaders.setAttribute("class", "two-cols-page-headers");
        lastModifiedPageHeaders.setAttribute("class", "last-modified-page-headers");
        datePageHeaders.setAttribute("class", "date-page-headers");
        wrapTwoColsElement.setAttribute("class", "two-cols-wrapper");
        wrapLeftColElement.setAttribute("class", "left-col-wrapper");
        wrapRightColElement.setAttribute("class", "right-col-wrapper");
        buttonElement.setAttribute("class", "clean-button");
        settingsElement.setAttribute("class", "settings-button");
        wrapElement.innerHTML = "<style>:host {position:fixed;z-index:99999;left:0;top:0;background-color:rgba(0,0,0,0.6) } .wrap-page-headers {position:fixed;z-index:99999;left:0;bottom:0;background-color:rgba(0,0,0,0.6);font-family:sans-serif;font-size:10pt;font-weight:normal;font-style:italic;color:#fff} .two-cols-page-headers {display:flex;flex-wrap:wrap;padding:4px 6px} .two-cols-wrapper { display:flex;align-items: center;color:#fff;font-family:sans-serif;font-size:12pt;font-weight:normal } button { color:#fff;transition-duration: 0.4s;font-size:14pt;line-height:14pt;font-weight:bolder;border:0px solid black;background-color:transparent;cursor:pointer;padding: 4px } button:hover { background-color:rgba(255,255,255,0.6);color:#000} .message-wrapper { transform: translateX(-100%);    animation: slide-in 0.2s forwards;padding:0px 6px;} @keyframes slide-in { 100% { transform: translateX(0%);} @keyframes slide-out { 0% { transform: translateX(0%); } 100% { transform: translateX(-100%); } } .slide-out { animation: slide-out 0.2s forwards; }</style><slot></slot>";

        buttonElement.innerHTML = "&#10227;";
        settingsElement.innerHTML = "&#9881;";
        lastModifiedPageHeaders.innerHTML = `Страница изменена <b>${timeAgo(WILDA_PAGE_HEADER['last-modified'])}</b>`;
        datePageHeaders.innerHTML = `, закеширована <b>${timeAgo(WILDA_PAGE_HEADER.date)}</b>`;
        wrapPageHeaders.appendChild(twoColsPageHeaders);
        twoColsPageHeaders.appendChild(lastModifiedPageHeaders);
        twoColsPageHeaders.appendChild(datePageHeaders);
        wrapElement.appendChild(wrapTwoColsElement);
        wrapElement.appendChild(wrapPageHeaders);
        wrapTwoColsElement.appendChild(wrapLeftColElement);
        wrapTwoColsElement.appendChild(wrapRightColElement);
        wrapLeftColElement.appendChild(settingsElement);
        wrapLeftColElement.appendChild(buttonElement);

        settingsElement.addEventListener("click", (e) => {
            window.location.href = '?editor';
        });

        buttonElement.addEventListener("click", (e) => {
            xhrGet('/?clean-cache', (e) => {

                const wrapMessageElement = document.createElement("div");
                wrapMessageElement.setAttribute("class", "message-wrapper");
                wrapRightColElement.appendChild(wrapMessageElement);
                if (e.target.response.status) {
                    wrapMessageElement.textContent = e.target.response.message;

                    let date = new Date(Date.now() + 86400e3);
                    date = date.toUTCString();
                    document.cookie = `clean-browser-cache=true; expires=${date}`;

                    setTimeout(() => {
                        window.location.href = `${window.location.href}`;
                    }, 2000);
                } else {
                    wrapMessageElement.textContent = 'Ошибка!';
                }

            });
            e.preventDefault();
        });

        this.shadowRoot.appendChild(wrapElement);
    }
}

window.customElements.define("wilda-cleaner", wildaCleaner);
