confirmLeave = false;
window.onbeforeunload = function(){
    if (confirmLeave)
        return document.getElementById("jsConfirmLeave").innerHTML;
};


function toggleAllCheckboxes(source, checkboxname) {
    var checkboxes = document.getElementsByName("chkComment[]");
    var n = checkboxes.length;

    for (var i=0; i<n; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function getCheckboxesCount() {
    var checkboxes = document.getElementsByName("chkComment[]");
    var n = checkboxes.length;
    var count = 0;

    for (var i=0; i<n; i++) {
        if (checkboxes[i].checked == true)
            count++;
    }
    return count;
}

function confirmDeleteCom() {
    var count = getCheckboxesCount();
    var strLocale = document.getElementById("jsDeleteRows").innerHTML;
    strLocale = strLocale.replace(/%count%/g, count);
    return window.confirm(strLocale);
}

function confirmFirst(action) {
    var strLocale = document.getElementById("js" + action).innerHTML;
    return window.confirm(strLocale);
}

function checkLatinOnly(elem) {
    var str = elem.value;
    var strLocale = document.getElementById("jsLatinCharsOnly").innerHTML;

    // remove all non-latin chars and check if string is altered
    if (str.length != str.replace(/[^a-zA-Z0-9]+/g, "").length)
        window.alert(strLocale);
}

function enableField(source) {
    if (source.readOnly) {
        var strLocale = document.getElementById("jsEnableField").innerHTML;
        window.alert(strLocale);
        source.readOnly = false;
    }
}

function toggleTextbuttons() {
    var textbuttons = document.getElementsByClassName("textbutton");
    var n = textbuttons.length;

    if (getCheckboxesCount() > 0) {
        for (var i=0; i<n; i++)
            textbuttons[i].style.borderColor = "#005869";
    } else {
        for (var i=0; i<n; i++)
            textbuttons[i].style.borderColor = "#fff";
    }
}

function generateUrlTitle(force) {
    var urlTitle = document.getElementsByName("urlTitle")[0];

    // if not forced (e.g. when called by onchange event from "title" field),
    // urlTitle has to be empty in order to prevent clearing user input
    if (force || urlTitle.value == "") {
        var longtitle = document.getElementsByName("title")[0].value;
        var longtitle = longtitle.toLowerCase();
        try {
            longtitle = longtitle.trim();
        } catch (e) { }

        longtitle = longtitle.replace(/ß/g, "ss");
        longtitle = longtitle.replace(/ä/g, "a");
        longtitle = longtitle.replace(/ö/g, "o");
        longtitle = longtitle.replace(/ü/g, "u");
        longtitle = longtitle.replace(/#/g, "-");
        longtitle = longtitle.replace(/&/g, "-");
        longtitle = longtitle.replace(/</g, "-");
        longtitle = longtitle.replace(/>/g, "-");
        longtitle = longtitle.replace(/ /g, "-");
        longtitle = longtitle.replace(/\(/g, "-");
        longtitle = longtitle.replace(/\)/g, "-");
        longtitle = longtitle.replace(/\./g, "-");
        longtitle = longtitle.replace(/\"/g, "-");
        longtitle = longtitle.replace(/\'/g, "-");

        urlTitle.value = longtitle;
    }
}

function getNow(elemName) {
    var elem = document.getElementsByName(elemName)[0];
    var now = new Date(new Date()+" UTC").toISOString().slice(0, 19).replace("T", " ");
    elem.value = now;
    return false;
}


function insertTag(elem, singleTag, newline) {
    var input = document.getElementsByName("content")[0];
    input.focus();
    // selection/cursor position
    var sPos = input.selectionStart;
    var ePos = input.selectionEnd;
    var insText = input.value.substring(sPos, ePos);
    
    // start tag already set? or only single tag needed?
    if (elem.value.substr(0, 1) == "/" ||
        singleTag) {
        var eTag;
        var pos;
        // special tag?
        // set cursor in src attribute of img tag
        if (elem.value == "img") {
            eTag = "<img src=\"\" alt=\"\" title=\"\">";
            pos = sPos + 10;
        // insert a new line after some tags
        } else if (newline) {
            eTag = "<" + elem.value + ">\n";
            pos = ePos + eTag.length;
        // no special tag
        } else {
            eTag = "<" + elem.value + ">";
            pos = ePos + eTag.length;
        }
        // insert
        input.value = input.value.substr(0, sPos) + eTag + insText + input.value.substr(ePos);
        // edit button (if not singleTag)
        if (!singleTag)
            elem.value = elem.value.substr(1);
    } else {
        var sTag;
        var pos;
        // special tag?
        if (elem.value == "a") {
            sTag = "<a href=\"\" title=\"\">";
            pos = sPos + 9;
        } else {
            sTag = "<" + elem.value + ">";
        }
        var eTag = "</" + elem.value + ">"

        // if no selection, insert only start tag
        if (insText.length == 0) {
            input.value = input.value.substr(0, sPos) + sTag + insText + input.value.substr(ePos);
            // edit button
            elem.value = "/" + elem.value;
        } else {
            input.value = input.value.substr(0, sPos) + sTag + insText + eTag + input.value.substr(ePos);
        }
        // calc cursor position if not already set (e.g. by <a> tag)
        if (!pos) {
            if (insText.length == 0) {
              pos = sPos + sTag.length;
            } else {
              pos = sPos + sTag.length + insText.length + eTag.length;
            }
        }
    }
    // set cursor
    input.selectionStart = pos;
    input.selectionEnd = pos;
}

function showPreview() {
    var postContent = document.getElementsByName("content")[0].value;
    var postTitle = document.getElementsByName("title")[0].value;
    var postAuthor = document.getElementsByName("authorId")[0].options[document.getElementsByName("authorId")[0].selectedIndex].text;
    var postCreated = document.getElementsByName("created")[0].value;
    var previewArea = document.getElementById("previewarea");
    // strip author and date
    postAuthor = postAuthor.substr(postAuthor.indexOf(" ") + 1);
    postCreated = postCreated.substr(0, postCreated.indexOf(" "));

    previewArea.innerHTML = (
        "<h3>" + postTitle + "</h3>\n"
      + '<p class="postdate vcard"><span class="fn author">' + postAuthor + "</span>, " + postCreated + "</p>\n"
      + postContent + "\n");
}


function loginWait(elem) {
    elem.style.opacity = 0.2;
    document.body.style.cursor = "wait";
    return true;
}


function keepAlive(firstrun) {
    if (!firstrun) {
        var req;
        try {
            req = window.XMLHttpRequest ? new XMLHttpRequest(): new ActiveXObject("Microsoft.XMLHTTP");
        } catch (e) {
            // browser does not have ajax support
        }
        
        req.open("GET", "keepalive.php", true);
        req.send();
    }
    // call every 10 min
    t = setTimeout(function(){keepAlive(false)}, 10 * 60 * 1000);
    return true;
}



function checkUpdate(url) {
    var strLocale = document.getElementById("jsUpdateChecking").innerHTML;
    var req, msg;
    try {
        req = window.XMLHttpRequest ? new XMLHttpRequest(): new ActiveXObject("Microsoft.XMLHTTP");
    } catch (e) {
        // browser does not have ajax support
    }
    req.onreadystatechange = function() {
        // print current readystate (countdown 4 to 0)
        document.getElementById("updateCheckResult").innerHTML = strLocale + " (" + (4 - req.readyState) + ")";
        if (req.readyState == 4 && req.status == 200) {
            var result = req.responseText;
            // if result is one of these, print locale version of them
            // else print original result
            if (result == "critical" || result == "new" || result == "ok") {
                msg = "jsUpdate_" + result;
                msg = document.getElementById(msg).innerHTML;
            } else {
                msg = result;
            }
            document.getElementById("updateCheckResult").innerHTML = msg;
        }
    }
    req.open("GET", url, true);
    req.send();
}
