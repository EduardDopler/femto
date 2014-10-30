/**
 * Main Javascript file.
 *
 * femto blog system.
 *
 * @author Eduard Dopler <contact@eduard-dopler.de>
 * @version 0.1
 * @license Apache License 2.0 http://www.apache.org/licenses/LICENSE-2.0.txt
 */

loading = false;
nextStartPost = 3;

// Load More button I
function loadMore(elem) {
    // prevent loading when already loading
    if (!loading) {
        loading = true;
        // print "loading"-like message
        elem.innerHTML = document.getElementById("jsLoading").innerHTML;
        // get current lang
        lang = document.getElementById("jsLang").innerHTML;
        // do Ajax request
        ajaxPost("/raw.php", "start=" + nextStartPost + "&count=5&l=" + lang, true);
        nextStartPost += 5;
    }
    loading = false;
    // disallow button action
    return false;
}
// Load More button II (Ajax request function)
function ajaxPost(url, postData, callback, button) {
    // initialize Ajax
    var req;
    try {
        req = window.XMLHttpRequest ? new XMLHttpRequest(): new ActiveXObject("Microsoft.XMLHTTP");
    } catch (e) {
        // browser does not have ajax support
    }
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            ajaxResult(req.responseText);
        }
    }
    // send POST request
    req.open("POST", url, true);
    req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    req.send(postData);

    return false;
}
// Load More button III (Ajax result function)
function ajaxResult(result) {
    // append result posts
    document.getElementsByTagName("main")[0].innerHTML += result;
    // hide this Button (it is sent in result anyway)
    buttoncontainer = document.getElementsByClassName("buttoncontainer")[0];
    buttoncontainer.parentElement.removeChild(buttoncontainer);
}


// Focus on input field in New Comment form.
// Show more details about the current input field when focussing in its placeholder tag.
function fieldFocus(elem) {
    // hide checkmark while focused
    if (elem.name != "content")
        elem.nextSibling.style.display = "none";

    // add additional info when focused first time
    switch (elem.name) {
        case "email":
            elem.placeholder = document.getElementById("jsFormEmail").innerHTML;
            break;
        case "url":
            elem.placeholder = document.getElementById("jsFormUrl").innerHTML;
            break;
        case "content":
            elem.placeholder = document.getElementById("jsFormComment").innerHTML.replace(/code/g, "<code>");
            break;
    }
}

// Input check.
function fieldCheck(elem) {
    var checkmark = false;

    switch (elem.name) {
        case "name":
            if (elem.value.length >= 2)
                checkmark = true;
            break;
        case "email":
            var mailPattern = /.+@.{2,}\..{2,}/;
            if (mailPattern.test(elem.value))
                checkmark = true;
            break;
        case "url":
            var urlPattern = /https?:\/\/.+\..{2,}/;
            var urlPatternShort = /.+\..{2,}/;
            if (urlPattern.test(elem.value)) {
                checkmark = true;
            } else {
                if (urlPatternShort.test(elem.value)) {
                    elem.value = "http://" + elem.value;

                    checkmark = true;
                }
            }
            break;
        case "math":
            var result = document.getElementById("result");
            if (elem.value == result.value)
                checkmark = true;
            break;
    }

    if (checkmark)
        elem.nextSibling.style.display = "inline";
}

// Calculate some math work to be filled out for simple spam protection.
function calcMath() {
    var mathElem = document.getElementById("math");
    var resultElem = document.getElementById("result");

    // is there a comment area on this page?
    if (mathElem && resultElem) {
        var rnd = parseInt((11-3+1) * Math.random() + 3);
        var seconds = new Date().getSeconds();
        var sum = rnd + seconds;

        mathElem.placeholder = seconds.toString() + "+" + rnd.toString() + " = ?";
        mathElem.value = "";
        // store result in order to be sent to server for verification
        resultElem.value = sum;
    }
}

// Show/hide navigation bar for small screens.
// Toggle "true" and "false" and append it to the anchor which "listens" for activation via CSS:target.
function toggleNav() {
    var elem = document.getElementById("show-nav");
    var anchor = elem.href.substring(elem.href.indexOf("#") + 1);
    var newAnchor = "#nav-" + !eval(anchor.substring(4));
    elem.href = newAnchor;
}
