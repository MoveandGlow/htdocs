const NewsletterPopup = {
// GDPR: used only for technical cookies
    set_cookie: function (name, value, time) {
        var e = new Date();
        e.setTime(e.getTime() + time * 24 * 60 * 60 * 1000);
        document.cookie = name + "=" + value + "; expires=" + e.toGMTString() + "; path=/";
    },
    get_cookie: function (name, def) {
        var cs = document.cookie.toString().split('; ');
        var c;
        for (var i = 0; i < cs.length; i++) {
            c = cs[i].split("=");
            if (c[0] == name)
                return c[1];
        }
        return def;
    },
    open: function () {
        fetch(tnp_popup_url).then(data => {
            data.text().then(body => {
                var modal_body = document.getElementById('tnp-modal-body');
                modal_body.innerHTML = body;
                var modal = document.getElementById('tnp-modal');
                modal.style.display = 'block';
                var form = document.getElementById('tnp-subscription-popup');
                //form.addEventListener("submit", NewsletterPopup.submit); // Do not use can be overwritten by other plugins
                form.onsubmit = NewsletterPopup.submit;
            });
        }).catch(error => {
            console.error(error);
        });
    },
    submit: async function (event) {
        event.preventDefault();
        var form = document.getElementById('tnp-subscription-popup');
        const data = new FormData(form);
        const response = await fetch(tnp_popup_action, {
            method: "POST",
            body: data,
        });
        const text = await response.text();
        var modal_body = document.getElementById('tnp-modal-body');
        modal_body.innerHTML = text;
    },
    close: function () {
        var modal = document.getElementById('tnp-modal');
        modal.style.display = 'none';
        NewsletterPopup.set_cookie("tnp-popup-closed", 1, 60);
    },
    outside_click: function (e) {
//        var modal = document.getElementById('tnp-modal');
//        if (e.target == modal) {
//            modal.style.display = 'none';
//        }
    },

}

//window.addEventListener('click', NewsletterPopup.outside_click);
document.getElementById('tnp-modal-close').addEventListener('click', NewsletterPopup.close);

if (tnp_popup_test) {
    NewsletterPopup.open();
} else {
    if (NewsletterPopup.get_cookie("tnp-popup-closed", null) === null) {
        var count = parseInt(NewsletterPopup.get_cookie("tnp-popup-count", 0)) + 1;
        NewsletterPopup.set_cookie("tnp-popup-count", count, 30);
        if (count >= 2) {
            setTimeout(NewsletterPopup.open, 5000);
        }
    }
}

