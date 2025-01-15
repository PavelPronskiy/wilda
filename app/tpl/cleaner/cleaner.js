
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
        const buttonElement = document.createElement("button");
        wrapTwoColsElement.setAttribute("class", "two-cols-wrapper");
        wrapLeftColElement.setAttribute("class", "left-col-wrapper");
        wrapRightColElement.setAttribute("class", "right-col-wrapper");
        buttonElement.setAttribute("class", "clean-button");
        wrapElement.innerHTML = "<style>:host {position:fixed;z-index:99999;left:0;top:0;padding:0px 6px;background-color:rgba(0,0,0,0.4) } .two-cols-wrapper { display:flex;align-items: center;color:#fff;font-family:sans-serif;font-size:12pt;font-weight:normal } button { color:#fff;font-size:16pt;font-weight:normal;padding:0px 0px 0px 0px;border:0px solid black;background-color:transparent;cursor:pointer } .message-wrapper { transform: translateX(-100%);    animation: slide-in 0.2s forwards;padding:0px 6px;} @keyframes slide-in { 100% { transform: translateX(0%);} @keyframes slide-out { 0% { transform: translateX(0%); } 100% { transform: translateX(-100%); } } .slide-out { animation: slide-out 0.2s forwards; }</style><slot></slot>";

        buttonElement.innerHTML = "&#10227;";

        wrapElement.appendChild(wrapTwoColsElement);
        wrapTwoColsElement.appendChild(wrapLeftColElement);
        wrapTwoColsElement.appendChild(wrapRightColElement);
        wrapLeftColElement.appendChild(buttonElement);


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
                }
                else
                {
                    wrapMessageElement.textContent = 'Ошибка!';
                }

            });
            e.preventDefault();
        });

        this.shadowRoot.appendChild(wrapElement);
    }
}
window.customElements.define("wilda-cleaner", wildaCleaner);
